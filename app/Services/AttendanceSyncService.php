<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSyncOperation;
use App\Models\ClassAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Applies offline attendance operations. Each operation is judged on its own, so one bad
 * operation never blocks the rest of a batch. A result is final only when the server says so:
 * applied (committed), conflict (parked, nothing overwritten), discarded (conflict resolved in
 * favour of the server) or rejected (nothing committed, nothing stored).
 */
class AttendanceSyncService
{
    /** @var array<int, ClassAssignment|null> */
    private array $classes = [];

    /** @var array<int, list<int>> */
    private array $rosters = [];

    public function __construct(private AttendanceService $attendance) {}

    /**
     * @param  list<array<string, mixed>>  $operations
     * @return list<array<string, mixed>>
     */
    public function process(User $user, array $operations): array
    {
        $this->classes = [];
        $this->rosters = [];

        // Versions this same batch already produced, so two queued edits of one student do not clash.
        $chain = [];
        $results = [];

        foreach ($operations as $op) {
            $results[] = $this->processOne($user, $op, $chain);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, int>  $chain
     * @return array<string, mixed>
     */
    private function processOne(User $user, array $op, array &$chain): array
    {
        $existing = AttendanceSyncOperation::where('operation_id', $op['operation_id'])->first();
        if ($existing) {
            return $this->replay($user, $existing, $op);
        }

        $reason = $this->rejectionReason($user, $op);
        if ($reason) {
            return $this->result($op['operation_id'], 'rejected', reason: $reason);
        }

        try {
            $outcome = $this->commit($user, $op, $chain);
        } catch (UniqueConstraintViolationException) {
            // Another request committed the same operation or attendance row first: judge again.
            $existing = AttendanceSyncOperation::where('operation_id', $op['operation_id'])->first();
            if ($existing) {
                return $this->replay($user, $existing, $op);
            }
            $outcome = $this->commit($user, $op, $chain);
        }

        if ($outcome['notify']) {
            $this->notifyGuardians($op);
        }

        return $outcome['result'];
    }

    private function rejectionReason(User $user, array $op): ?string
    {
        $ca = $this->classFor((int) $op['class_assignment_id']);
        if (! $ca) {
            return 'class_not_found';
        }
        if ($ca->trashed() || $ca->status !== 'active') {
            return 'class_archived';
        }
        if (! Gate::forUser($user)->allows('recordAttendance', $ca)) {
            return 'forbidden';
        }
        if (! in_array((int) $op['student_id'], $this->rosterIds($ca), true)) {
            return 'not_enrolled';
        }
        if ($op['date'] > Carbon::now(config('monitoring.timezone'))->toDateString()) {
            return 'future_date';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $op
     * @param  array<string, int>  $chain
     * @return array{result: array<string, mixed>, notify: bool}
     */
    private function commit(User $user, array $op, array &$chain): array
    {
        $chainKey = $op['class_assignment_id'].'|'.$op['student_id'].'|'.$op['date'];
        $hash = $this->payloadHash($op);

        return DB::transaction(function () use ($user, $op, &$chain, $chainKey, $hash) {
            $row = Attendance::where('student_id', $op['student_id'])
                ->where('class_assignment_id', $op['class_assignment_id'])
                ->whereDate('attendance_date', $op['date'])
                ->lockForUpdate()
                ->first();

            $current = $row ? (int) $row->sync_version : 0;
            $expected = $chain[$chainKey] ?? (int) $op['base_version'];
            $remarks = $op['remarks'] ?? null;

            $receipt = [
                'operation_id' => $op['operation_id'],
                'user_id' => $user->id,
                'class_assignment_id' => $op['class_assignment_id'],
                'student_id' => $op['student_id'],
                'attendance_date' => $op['date'],
                'status' => $op['status'],
                'remarks' => $remarks,
                'base_version' => $op['base_version'],
                'payload_hash' => $hash,
            ];

            if ($current !== $expected) {
                // Same mark already on the server: converge without writing anything.
                if ($row && $row->status === $op['status'] && $row->remarks === $remarks) {
                    AttendanceSyncOperation::create($receipt + ['outcome' => 'applied', 'server_version' => $current]);

                    return ['result' => $this->result($op['operation_id'], 'applied', $current), 'notify' => false];
                }

                AttendanceSyncOperation::create($receipt + ['outcome' => 'conflict', 'server_version' => $current]);

                return ['result' => $this->result(
                    $op['operation_id'], 'conflict', $current,
                    reason: 'stale_version',
                    server: $this->serverState($row),
                ), 'notify' => false];
            }

            $old = $row ? ['status' => $row->status, 'remarks' => $row->remarks, 'version' => $current] : null;
            $row = $this->writeAttendance($user, $row, $op['class_assignment_id'], $op['student_id'], $op['date'], $op['status'], $remarks);
            $chain[$chainKey] = (int) $row->sync_version;

            AttendanceSyncOperation::create($receipt + ['outcome' => 'applied', 'server_version' => $row->sync_version]);
            AuditLogger::log($user, $row, 'attendance.sync_applied', $old, [
                'status' => $row->status, 'remarks' => $row->remarks, 'version' => $row->sync_version,
                'operation_id' => $op['operation_id'],
            ]);

            return ['result' => $this->result($op['operation_id'], 'applied', (int) $row->sync_version), 'notify' => true];
        });
    }

    /**
     * Resolve a parked conflict. keep_local writes the device's mark over the current server mark;
     * use_server drops the device's mark. Either way the previous server values stay in the audit log.
     *
     * @return array<string, mixed>
     */
    public function resolve(User $user, AttendanceSyncOperation $operation, string $resolution): array
    {
        if ($operation->outcome !== 'conflict') {
            if ($operation->resolution === $resolution) {
                return $this->resolvedResult($operation);
            }

            throw ValidationException::withMessages([
                'resolution' => ['This conflict was already resolved differently.'],
            ]);
        }

        $notify = false;

        DB::transaction(function () use ($user, $operation, $resolution, &$notify) {
            $row = Attendance::where('student_id', $operation->student_id)
                ->where('class_assignment_id', $operation->class_assignment_id)
                ->whereDate('attendance_date', $operation->attendance_date->toDateString())
                ->lockForUpdate()
                ->first();

            if ($resolution === 'keep_local') {
                $old = $row ? ['status' => $row->status, 'remarks' => $row->remarks, 'version' => $row->sync_version] : null;
                $row = $this->writeAttendance(
                    $user, $row, $operation->class_assignment_id, $operation->student_id,
                    $operation->attendance_date->toDateString(), $operation->status, $operation->remarks,
                );
                $operation->update([
                    'outcome' => 'applied', 'server_version' => $row->sync_version,
                    'resolution' => $resolution, 'resolved_by' => $user->id, 'resolved_at' => now(),
                ]);
                AuditLogger::log($user, $row, 'attendance.conflict_keep_local', $old, [
                    'status' => $row->status, 'remarks' => $row->remarks, 'version' => $row->sync_version,
                    'operation_id' => $operation->operation_id,
                ]);
                $notify = true;

                return;
            }

            $operation->update([
                'outcome' => 'discarded', 'server_version' => $row ? $row->sync_version : 0,
                'resolution' => $resolution, 'resolved_by' => $user->id, 'resolved_at' => now(),
            ]);
            AuditLogger::log($user, $operation, 'attendance.conflict_use_server',
                ['status' => $operation->status, 'remarks' => $operation->remarks], null);
        });

        if ($notify) {
            $this->notifyGuardians([
                'class_assignment_id' => $operation->class_assignment_id,
                'student_id' => $operation->student_id,
                'date' => $operation->attendance_date->toDateString(),
                'status' => $operation->status,
            ]);
        }

        return $this->resolvedResult($operation->refresh());
    }

    /** Unresolved conflicts the server holds for this user (device-local work is invisible here). */
    public function unresolvedConflictCount(User $user): int
    {
        return AttendanceSyncOperation::where('user_id', $user->id)
            ->where('outcome', 'conflict')
            ->whereIn('class_assignment_id', ClassAssignment::query()->where('status', 'active')->select('id'))
            ->count();
    }

    private function writeAttendance(User $user, ?Attendance $row, int $classId, int $studentId, string $date, string $status, ?string $remarks): Attendance
    {
        if ($row) {
            $row->update([
                'status' => $status, 'remarks' => $remarks, 'recorded_by' => $user->id,
                'sync_version' => $row->sync_version + 1,
            ]);

            return $row;
        }

        return Attendance::create([
            'student_id' => $studentId, 'class_assignment_id' => $classId, 'attendance_date' => $date,
            'status' => $status, 'remarks' => $remarks, 'recorded_by' => $user->id, 'sync_version' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function replay(User $user, AttendanceSyncOperation $existing, array $op): array
    {
        if ((int) $existing->user_id !== (int) $user->id || $existing->payload_hash !== $this->payloadHash($op)) {
            return $this->result($op['operation_id'], 'rejected', reason: 'operation_id_reused');
        }

        if ($existing->outcome === 'conflict') {
            $row = Attendance::where('student_id', $existing->student_id)
                ->where('class_assignment_id', $existing->class_assignment_id)
                ->whereDate('attendance_date', $existing->attendance_date->toDateString())
                ->first();

            return $this->result($existing->operation_id, 'conflict', (int) $existing->server_version,
                replayed: true, reason: 'stale_version', server: $this->serverState($row));
        }

        return $this->result($existing->operation_id, $existing->outcome, (int) $existing->server_version, replayed: true);
    }

    /** @return array<string, mixed> */
    private function resolvedResult(AttendanceSyncOperation $operation): array
    {
        $row = Attendance::where('student_id', $operation->student_id)
            ->where('class_assignment_id', $operation->class_assignment_id)
            ->whereDate('attendance_date', $operation->attendance_date->toDateString())
            ->first();

        return $this->result($operation->operation_id, $operation->outcome, (int) $operation->server_version,
            server: $this->serverState($row));
    }

    /**
     * @return array<string, mixed>
     */
    private function result(string $operationId, string $outcome, ?int $version = null, bool $replayed = false, ?string $reason = null, ?array $server = null): array
    {
        return [
            'operation_id' => $operationId,
            'outcome' => $outcome,
            'replayed' => $replayed,
            'version' => $version,
            'reason' => $reason,
            'server' => $server,
        ];
    }

    /** @return array{status: ?string, remarks: ?string, version: int} */
    private function serverState(?Attendance $row): array
    {
        return [
            'status' => $row?->status,
            'remarks' => $row?->remarks,
            'version' => $row ? (int) $row->sync_version : 0,
        ];
    }

    /** Identity of what the device asked for. The base version is part of it on purpose. */
    private function payloadHash(array $op): string
    {
        return hash('sha256', json_encode([
            (int) $op['class_assignment_id'], (int) $op['student_id'], $op['date'],
            $op['status'], $op['remarks'] ?? null, (int) $op['base_version'],
        ]));
    }

    private function classFor(int $id): ?ClassAssignment
    {
        return $this->classes[$id] ??= ClassAssignment::withTrashed()->find($id);
    }

    /** @return list<int> */
    private function rosterIds(ClassAssignment $ca): array
    {
        return $this->rosters[$ca->id] ??= $this->attendance->roster($ca)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Guardian emails must never stop a confirmed save, same as the bulk endpoint. */
    private function notifyGuardians(array $op): void
    {
        try {
            $ca = ClassAssignment::find($op['class_assignment_id']);
            if ($ca) {
                app(GuardianNotificationService::class)->queueForSave($ca, $op['date'], [
                    ['student_id' => $op['student_id'], 'status' => $op['status']],
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin correction of one mark. It moves the sync version like any other write, so a phone holding an
 * older version gets a conflict instead of silently overwriting the correction.
 */
class AttendanceCorrectionService
{
    public function __construct(private AttendanceService $attendance) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{stale: bool, attendance: ?Attendance, current: array{status: ?string, remarks: ?string, version: int}}
     */
    public function correct(User $admin, array $data): array
    {
        $ca = ClassAssignment::withTrashed()->find($data['class_assignment_id']);

        if (! $ca || $ca->trashed() || $ca->status !== 'active') {
            throw ValidationException::withMessages(['class_assignment_id' => ['This class is archived or does not exist.']]);
        }
        if (! $this->attendance->roster($ca)->contains('id', (int) $data['student_id'])) {
            throw ValidationException::withMessages(['student_id' => ['This student is not enrolled in the class.']]);
        }
        if ($data['date'] > Carbon::now(config('monitoring.timezone'))->toDateString()) {
            throw ValidationException::withMessages(['date' => ['Attendance cannot be corrected for a future date.']]);
        }

        $notify = false;

        $result = DB::transaction(function () use ($admin, $data, &$notify) {
            $row = Attendance::where('student_id', $data['student_id'])
                ->where('class_assignment_id', $data['class_assignment_id'])
                ->whereDate('attendance_date', $data['date'])
                ->lockForUpdate()
                ->first();

            $current = $row ? (int) $row->sync_version : 0;
            $remarks = $data['remarks'] ?? null;

            if ($current !== (int) $data['version']) {
                return ['stale' => true, 'attendance' => null, 'current' => $this->state($row)];
            }

            $old = $row ? ['status' => $row->status, 'remarks' => $row->remarks, 'version' => $current] : null;

            if ($row) {
                $row->update([
                    'status' => $data['status'], 'remarks' => $remarks,
                    'recorded_by' => $admin->id, 'sync_version' => $current + 1,
                ]);
            } else {
                $row = Attendance::create([
                    'student_id' => $data['student_id'], 'class_assignment_id' => $data['class_assignment_id'],
                    'attendance_date' => $data['date'], 'status' => $data['status'], 'remarks' => $remarks,
                    'recorded_by' => $admin->id, 'sync_version' => 1,
                ]);
            }

            AuditLogger::log($admin, $row, 'attendance.admin_corrected', $old, [
                'status' => $row->status, 'remarks' => $row->remarks, 'version' => $row->sync_version,
                'reason' => $data['reason'],
            ]);
            $notify = $old === null || $old['status'] !== $row->status;

            return ['stale' => false, 'attendance' => $row, 'current' => $this->state($row)];
        });

        if ($notify) {
            // Same rules as a normal save: one notice per student, date and status, and never a failure.
            try {
                app(GuardianNotificationService::class)->queueForSave($ca, $data['date'], [
                    ['student_id' => (int) $data['student_id'], 'status' => $data['status']],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $result;
    }

    /** @return array{status: ?string, remarks: ?string, version: int} */
    private function state(?Attendance $row): array
    {
        return ['status' => $row?->status, 'remarks' => $row?->remarks, 'version' => $row ? (int) $row->sync_version : 0];
    }
}

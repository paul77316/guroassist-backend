<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\InterventionFollowup;
use App\Models\Student;
use App\Models\User;
use App\Support\InterventionStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Interventions and their follow-up history. Operational tracking only; nothing here is a grade or a verdict.
 * This is the only place that changes status, follow_up_date or outcome.
 * Audit values hold ids, statuses, dates and "text changed" flags, never free text.
 */
class InterventionService
{
    public const OUTCOMES = ['improved', 'partly_improved', 'no_change', 'not_yet_known'];

    public function __construct(private StudentRecordGuard $guard, private TeacherStudentScope $scope) {}

    // ---------------------------------------------------------------- create / edit

    public function create(User $user, Student $student, array $data): Intervention
    {
        $classId = ! empty($data['class_assignment_id']) ? (int) $data['class_assignment_id'] : null;
        $this->guard->assertContext($user, $student, $classId);

        $followUp = $data['follow_up_date'] ?? null;
        if ($followUp && $followUp < $data['intervention_date']) {
            throw ValidationException::withMessages(['follow_up_date' => ['The follow-up date cannot be before the intervention date.']]);
        }

        return DB::transaction(function () use ($user, $student, $data, $classId, $followUp) {
            $i = new Intervention(Arr::only($data, ['concern_type', 'concern', 'intervention_type', 'intervention_date', 'notes']));
            // Server-controlled fields: never taken from the request.
            $i->student_id = $student->id;
            $i->class_assignment_id = $classId;
            $i->author_id = $user->id;
            $i->status = InterventionStatus::PLANNED;
            $i->follow_up_date = $followUp;
            $i->save();

            AuditLogger::log($user, $i, 'created', null, $this->values($i));

            return $i;
        });
    }

    /**
     * Edit the descriptive fields only. Status, follow-up date and outcome change through addEntry().
     * An admin editing someone else's record is logged as "admin_corrected".
     */
    public function update(User $user, Intervention $i, array $data): Intervention
    {
        $date = $data['intervention_date'];
        $errors = [];

        if ($i->follow_up_date && $i->follow_up_date->format('Y-m-d') < $date) {
            $errors['intervention_date'][] = 'The intervention date cannot be after the current follow-up date.';
        }
        $earliest = InterventionFollowup::where('intervention_id', $i->id)->min('followup_date');
        if ($earliest && substr((string) $earliest, 0, 10) < $date) {
            $errors['intervention_date'][] = 'Some follow-up entries are dated before this date. Correct those first.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $payload = Arr::only($data, ['concern_type', 'concern', 'intervention_type', 'intervention_date', 'notes']);
        $flags = [
            'concern_changed' => array_key_exists('concern', $payload) && $payload['concern'] !== $i->concern,
            'notes_changed' => array_key_exists('notes', $payload) && $payload['notes'] !== $i->notes,
        ];
        $old = $this->values($i);

        return DB::transaction(function () use ($user, $i, $payload, $old, $flags) {
            $i->update($payload);
            $event = $user->id === $i->author_id ? 'updated' : 'admin_corrected';
            AuditLogger::log($user, $i, $event, $old, $this->values($i->refresh()) + $flags);

            return $i;
        });
    }

    // ---------------------------------------------------------------- follow-up entries

    /**
     * Record one event on an intervention: a follow-up, a reschedule, a completion or a reopen.
     * Data: followup_date (required), kind ('followup' default | 'reschedule'), to_status (default: keep),
     *       progress, notes, next_follow_up_date, outcome (to complete), expected_status (stale-request guard).
     * The entry type is decided here from the transition, never taken from the client.
     */
    public function addEntry(User $user, Intervention $intervention, array $data): InterventionFollowup
    {
        $today = $this->guard->today();

        return DB::transaction(function () use ($user, $intervention, $data, $today) {
            // Lock the row so two requests cannot both act on the same old status.
            $i = Intervention::query()->lockForUpdate()->find($intervention->id);
            if (! $i) {
                throw ValidationException::withMessages(['intervention' => ['This intervention was deleted.']]);
            }

            $from = $i->status;
            $expected = $data['expected_status'] ?? null;
            if ($expected !== null && $expected !== $from) {
                throw ValidationException::withMessages(['status' => ['This intervention changed since you opened it. Reload and try again.']]);
            }

            $kind = $data['kind'] ?? 'followup';
            $to = $kind === 'reschedule' ? $from : ($data['to_status'] ?? $from);
            $date = $data['followup_date'];
            $next = $data['next_follow_up_date'] ?? null;
            $notes = trim((string) ($data['notes'] ?? ''));
            $errors = [];

            if (! in_array($kind, ['followup', 'reschedule'], true)) {
                $errors['kind'][] = 'Unknown entry kind.';
            }
            if ($date > $today) {
                $errors['followup_date'][] = 'The date cannot be in the future.';
            }
            if ($date < $i->intervention_date->format('Y-m-d')) {
                $errors['followup_date'][] = 'The date cannot be before the intervention date.';
            }

            // Transition rules (one table in InterventionStatus).
            if (! in_array($to, InterventionStatus::ALL, true)) {
                $errors['to_status'][] = 'Unknown status.';
            } elseif ($to === $from) {
                $stays = $kind === 'reschedule'
                    ? $from !== InterventionStatus::COMPLETED
                    : in_array($from, [InterventionStatus::IN_PROGRESS, InterventionStatus::REQUIRES_FOLLOW_UP], true);
                if (! $stays) {
                    $errors['to_status'][] = 'This intervention is ' . InterventionStatus::label($from) . ' and must move to a new status.';
                }
            } elseif (! InterventionStatus::canMove($from, $to)) {
                $errors['to_status'][] = 'Cannot change from ' . InterventionStatus::label($from) . ' to ' . InterventionStatus::label($to) . '.';
            }

            $complete = $to === InterventionStatus::COMPLETED && $from !== InterventionStatus::COMPLETED;
            $reopen = $from === InterventionStatus::COMPLETED && $to === InterventionStatus::IN_PROGRESS;

            if ($complete) {
                if (empty($data['outcome']) || ! in_array($data['outcome'], self::OUTCOMES, true)) {
                    $errors['outcome'][] = 'An outcome is required to complete an intervention.';
                }
                $next = null; // Completed clears the active due date.
            }
            if ($reopen && $notes === '') {
                $errors['notes'][] = 'A reason is required to reopen an intervention.';
            }
            if ($to === InterventionStatus::REQUIRES_FOLLOW_UP) {
                if ($notes === '') $errors['notes'][] = 'A reason is required for Requires Follow-up.';
                if ($next === null) $errors['next_follow_up_date'][] = 'A next follow-up date is required for Requires Follow-up.';
            }
            if ($kind === 'reschedule' && $next === null) {
                $errors['next_follow_up_date'][] = 'A new follow-up date is required to reschedule.';
            }
            if ($next !== null && $next <= $date) {
                $errors['next_follow_up_date'][] = 'The next follow-up date must be after the date of this entry.';
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            $type = $complete ? InterventionFollowup::TYPE_COMPLETED
                : ($reopen ? InterventionFollowup::TYPE_REOPENED
                : ($kind === 'reschedule' ? InterventionFollowup::TYPE_RESCHEDULED : InterventionFollowup::TYPE_FOLLOWUP));

            $old = $this->state($i);

            $entry = InterventionFollowup::create([
                'intervention_id' => $i->id,
                'author_id' => $user->id,
                'entry_type' => $type,
                'followup_date' => $date,
                'from_status' => $from,
                'to_status' => $to,
                'progress' => $kind === 'reschedule' ? null : ($data['progress'] ?? null),
                'notes' => $notes !== '' ? $notes : null,
                'next_follow_up_date' => $next,
                'outcome' => $complete ? $data['outcome'] : null, // the history keeps what was concluded, even after a reopen
            ]);

            $i->status = $to;
            $i->follow_up_date = $next;
            if ($complete) $i->outcome = $data['outcome'];
            if ($reopen) $i->outcome = null; // the earlier outcome stays in the audit log
            $i->save();

            $event = [
                InterventionFollowup::TYPE_FOLLOWUP => 'followup_recorded',
                InterventionFollowup::TYPE_RESCHEDULED => 'rescheduled',
                InterventionFollowup::TYPE_COMPLETED => 'completed',
                InterventionFollowup::TYPE_REOPENED => 'reopened',
            ][$type];

            AuditLogger::log($user, $i, $event, $old, $this->state($i->refresh()) + [
                'followup_id' => $entry->id,
                'by_admin' => $user->id !== $i->author_id,
            ]);

            return $entry;
        });
    }

    // ---------------------------------------------------------------- delete / restore

    public function delete(User $user, Intervention $i): void
    {
        DB::transaction(function () use ($user, $i) {
            AuditLogger::log($user, $i, 'deleted', $this->values($i), null);
            $i->delete();
        });
    }

    public function restore(User $user, Intervention $i): void
    {
        DB::transaction(function () use ($user, $i) {
            $i->restore();
            AuditLogger::log($user, $i, 'restored', null, $this->values($i));
        });
    }

    // ---------------------------------------------------------------- reading

    /**
     * Interventions for a student. Teachers see their own; admins see all authors (one audit row per request).
     * Archived-class records are excluded for everyone.
     */
    public function listForStudent(User $user, Student $student, ?int $classId = null, bool $trashedOnly = false): Collection
    {
        Gate::forUser($user)->authorize('view', $student);

        $rows = ($trashedOnly ? Intervention::onlyTrashed() : Intervention::query())
            ->where('student_id', $student->id)
            ->inActiveClasses()
            ->when($classId, fn ($q) => $q->where('class_assignment_id', $classId))
            ->when(! $user->isAdmin(), fn ($q) => $q->authoredBy($user->id))
            ->orderByDesc('intervention_date')->orderByDesc('id')
            ->get();

        $this->guard->auditAdminRead($user, $student, 'intervention', $rows->pluck('id')->all());

        return $rows;
    }

    /**
     * The user's own follow-ups that are due (follow_up_date <= today in the school timezone, not Completed,
     * not deleted, not in an archived class). Calculated on request, never stored. Reused later by the
     * dashboard and notifications.
     */
    public function due(User $user, ?string $today = null): Collection
    {
        $query = Intervention::query()
            ->with('student')
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->due($today ?? $this->guard->today());

        // The student must still be in one of the user's active classes, like the all-interventions list.
        return $this->scope->apply($query, $user)
            ->orderBy('follow_up_date')->orderBy('id')
            ->get();
    }

    // ---------------------------------------------------------------- internals

    private function state(Intervention $i): array
    {
        return [
            'status' => $i->status,
            'follow_up_date' => $i->follow_up_date?->format('Y-m-d'),
            'outcome' => $i->outcome,
        ];
    }

    private function values(Intervention $i): array
    {
        return [
            'student_id' => $i->student_id,
            'class_assignment_id' => $i->class_assignment_id,
            'concern_type' => $i->concern_type,
            'intervention_type' => $i->intervention_type,
            'intervention_date' => $i->intervention_date?->format('Y-m-d'),
        ] + $this->state($i);
    }
}

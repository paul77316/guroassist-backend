<?php

namespace App\Services;

use App\Models\ParentCommunication;
use App\Models\Student;
use App\Models\User;
use App\Support\InterventionVocabulary;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Log of parent contact the teacher already made. It sends nothing.
 * Reason and summary text are never written to the audit log.
 */
class ParentCommunicationService
{
    public function __construct(private StudentRecordGuard $guard, private TeacherStudentScope $scope) {}

    public function create(User $user, Student $student, array $data): ParentCommunication
    {
        $classId = ! empty($data['class_assignment_id']) ? (int) $data['class_assignment_id'] : null;
        $this->guard->assertContext($user, $student, $classId);
        $this->checkDates($data);

        return DB::transaction(function () use ($user, $student, $data, $classId) {
            $c = new ParentCommunication(Arr::only($data, ['contact_date', 'method', 'reason', 'summary', 'follow_up_date']));
            $c->student_id = $student->id;
            $c->class_assignment_id = $classId;
            $c->author_id = $user->id;
            $c->save();

            AuditLogger::log($user, $c, 'created', null, $this->values($c));

            return $c;
        });
    }

    public function update(User $user, ParentCommunication $c, array $data): ParentCommunication
    {
        $this->checkDates($data);
        $payload = Arr::only($data, ['contact_date', 'method', 'reason', 'summary', 'follow_up_date']);

        // A new or removed follow-up date means a new follow-up: the old "done" date no longer applies.
        $clearsDone = $c->follow_up_done_at !== null
            && array_key_exists('follow_up_date', $payload)
            && ($payload['follow_up_date'] ?: null) !== $c->follow_up_date?->format('Y-m-d');

        $flags = [
            'reason_changed' => array_key_exists('reason', $payload) && $payload['reason'] !== $c->reason,
            'summary_changed' => array_key_exists('summary', $payload) && $payload['summary'] !== $c->summary,
            'follow_up_done_cleared' => $clearsDone,
        ];
        $old = $this->values($c);

        return DB::transaction(function () use ($user, $c, $payload, $old, $flags, $clearsDone) {
            $c->fill($payload);
            if ($clearsDone) {
                $c->follow_up_done_at = null; // not fillable: only the server touches it
            }
            $c->save();

            $event = $user->id === $c->author_id ? 'updated' : 'admin_corrected';
            AuditLogger::log($user, $c, $event, $old, $this->values($c->refresh()) + $flags);

            return $c;
        });
    }

    /** Stamps today (school timezone). The client never sends the date. */
    public function markFollowUpDone(User $user, ParentCommunication $c): ParentCommunication
    {
        return DB::transaction(function () use ($user, $c) {
            $row = $this->lockedAvailable($c);

            if ($row->follow_up_date === null) {
                throw ValidationException::withMessages(['follow_up_date' => ['This contact has no follow-up date.']]);
            }
            if ($row->follow_up_done_at !== null) {
                throw ValidationException::withMessages(['follow_up_done_at' => ['This follow-up is already marked done.']]);
            }

            $old = $this->values($row);
            $row->follow_up_done_at = $this->guard->today();
            $row->save();

            $this->logDoneChange($user, $row, 'follow_up_done', $old);

            return $row;
        });
    }

    /** Undo: clears the done date. Audited. */
    public function undoFollowUpDone(User $user, ParentCommunication $c): ParentCommunication
    {
        return DB::transaction(function () use ($user, $c) {
            $row = $this->lockedAvailable($c);

            if ($row->follow_up_done_at === null) {
                throw ValidationException::withMessages(['follow_up_done_at' => ['This follow-up is not marked done.']]);
            }

            $old = $this->values($row);
            $row->follow_up_done_at = null;
            $row->save();

            $this->logDoneChange($user, $row, 'follow_up_undone', $old);

            return $row;
        });
    }

    public function delete(User $user, ParentCommunication $c): void
    {
        DB::transaction(function () use ($user, $c) {
            AuditLogger::log($user, $c, 'deleted', $this->values($c), null);
            $c->delete();
        });
    }

    public function restore(User $user, ParentCommunication $c): void
    {
        DB::transaction(function () use ($user, $c) {
            $c->restore();
            AuditLogger::log($user, $c, 'restored', null, $this->values($c));
        });
    }

    public function listForStudent(User $user, Student $student, ?int $classId = null, bool $trashedOnly = false): Collection
    {
        Gate::forUser($user)->authorize('view', $student);

        $rows = ($trashedOnly ? ParentCommunication::onlyTrashed() : ParentCommunication::query())
            ->where('student_id', $student->id)
            ->inActiveClasses()
            ->when($classId, fn ($q) => $q->where('class_assignment_id', $classId))
            ->when(! $user->isAdmin(), fn ($q) => $q->authoredBy($user->id))
            ->orderByDesc('contact_date')->orderByDesc('id')
            ->get();

        $this->guard->auditAdminRead($user, $student, 'parent_communication', $rows->pluck('id')->all());

        return $rows;
    }

    /** The user's own parent-contact follow-ups that are due. Calculated, never stored. */
    public function due(User $user, ?string $today = null): Collection
    {
        $query = ParentCommunication::query()
            ->with('student')
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->whereNotNull('follow_up_date')
            ->whereNull('follow_up_done_at')
            ->where('follow_up_date', '<=', $today ?? $this->guard->today());

        return $this->scope->apply($query, $user)
            ->orderBy('follow_up_date')->orderBy('id')
            ->get();
    }

    /**
     * Re-reads the row inside the transaction with a lock, so two tabs cannot both change it.
     * Deleted rows and rows of an archived class are not found.
     */
    private function lockedAvailable(ParentCommunication $c): ParentCommunication
    {
        $row = ParentCommunication::query()
            ->inActiveClasses()
            ->whereKey($c->getKey())
            ->lockForUpdate()
            ->first();

        if (! $row) {
            throw ValidationException::withMessages(['communication' => ['This communication is not available.']]);
        }

        return $row;
    }

    /** The author's own change is a normal event; an admin acting on someone else's record is a correction. */
    private function logDoneChange(User $user, ParentCommunication $row, string $event, array $old): void
    {
        $event = $user->id === $row->author_id ? $event : 'admin_corrected';

        AuditLogger::log($user, $row, $event, $old, $this->values($row) + ['follow_up_done_changed' => true]);
    }

    /** The log records contact that already happened: no future contact date; follow-up not before it. */
    private function checkDates(array $data): void
    {
        $errors = [];
        if ($data['contact_date'] > $this->guard->today()) {
            $errors['contact_date'][] = 'The contact date cannot be in the future.';
        }
        if (! empty($data['follow_up_date']) && $data['follow_up_date'] < $data['contact_date']) {
            $errors['follow_up_date'][] = 'The follow-up date cannot be before the contact date.';
        }
        if (! in_array($data['method'] ?? null, InterventionVocabulary::keys('METHODS'), true)) {
            $errors['method'][] = 'Choose a valid contact method.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function values(ParentCommunication $c): array
    {
        return [
            'student_id' => $c->student_id,
            'class_assignment_id' => $c->class_assignment_id,
            'contact_date' => $c->contact_date?->format('Y-m-d'),
            'method' => $c->method,
            'follow_up_date' => $c->follow_up_date?->format('Y-m-d'),
            'follow_up_done_at' => $c->follow_up_done_at?->format('Y-m-d'),
        ];
    }
}

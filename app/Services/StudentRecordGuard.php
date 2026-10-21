<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Checks shared by notes, interventions and parent communications.
 * The server decides ownership and class membership; the client only names a student and a class.
 */
class StudentRecordGuard
{
    public function __construct(private AttendanceService $attendance) {}

    /** "Today" in the school timezone (config monitoring.timezone, set with SCHOOL_TIMEZONE). */
    public function today(): string
    {
        return Carbon::now(config('monitoring.timezone'))->toDateString();
    }

    /**
     * May this user attach a NEW record to this student (and optional class)?
     * Student must be visible (StudentPolicy::view). A class must be active, belong to the user
     * (admins excepted) and have the student on its roster. Returns the class, or null for a general record.
     */
    public function assertContext(User $user, Student $student, ?int $classId): ?ClassAssignment
    {
        Gate::forUser($user)->authorize('view', $student);

        if ($classId === null) {
            return null;
        }

        // Archived classes are soft-deleted, so find() already leaves them out; status is a second guard.
        // One message for "missing", "archived" and "not yours" so ids cannot be probed.
        $ca = ClassAssignment::query()->find($classId);
        $mine = $ca && ($user->isAdmin() || ($user->teacher && $ca->teacher_id === $user->teacher->id));
        if (! $ca || $ca->status !== 'active' || ! $mine) {
            throw ValidationException::withMessages([
                'class_assignment_id' => ['This class is not available for new records.'],
            ]);
        }

        if (! $this->attendance->roster($ca)->pluck('id')->contains($student->id)) {
            throw ValidationException::withMessages([
                'class_assignment_id' => ['This student is not enrolled in the selected class.'],
            ]);
        }

        return $ca;
    }

    /**
     * One audit row per admin READ request (not per record). Ids and record type only, never text.
     * Does nothing for teachers or empty results.
     */
    public function auditAdminRead(User $user, Student $student, string $recordType, array $ids): void
    {
        if (! $user->isAdmin() || $ids === []) {
            return;
        }

        AuditLogger::log($user, $student, 'admin_read', null, [
            'record_type' => $recordType,
            'record_ids' => array_values($ids),
        ]);
    }
}

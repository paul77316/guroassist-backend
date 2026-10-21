<?php

namespace App\Policies;

use App\Models\ClassAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Rules shared by notes, interventions and parent communications.
 * There is deliberately NO before(): admins are not let through blindly, because records of an
 * archived class are hidden from everyone, admins included.
 *
 * A record is accessible when ALL hold:
 *  1. the user is an admin or the author of the record;
 *  2. the record's class (if any) is active, i.e. not archived;
 *  3. the student is visible to the user (StudentPolicy::view; admins pass).
 * view / update / delete / restore all use the same rule.
 */
abstract class AuthoredRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /** Creating: the student and class checks need the payload, so StudentRecordGuard does them. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Model $record): bool
    {
        return $this->canAccess($user, $record);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->canAccess($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->canAccess($user, $record);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->canAccess($user, $record);
    }

    protected function canAccess(User $user, Model $record): bool
    {
        if (! $user->isAdmin() && $record->author_id !== $user->id) {
            return false;
        }

        if (! $this->classIsAvailable($record)) {
            return false;
        }

        $student = $record->student;

        return $student !== null && $user->can('view', $student);
    }

    /** No class = always available. Archived classes are soft-deleted, so the default scope leaves them out. */
    private function classIsAvailable(Model $record): bool
    {
        if ($record->class_assignment_id === null) {
            return true;
        }

        return ClassAssignment::query()
            ->whereKey($record->class_assignment_id)
            ->where('status', 'active')
            ->exists();
    }
}

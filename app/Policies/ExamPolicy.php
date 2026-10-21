<?php

namespace App\Policies;

use App\Models\ClassAssignment;
use App\Models\Exam;
use App\Models\User;

/**
 * An exam is accessible when the user is its author or an admin, and its class is active.
 * No before(): exams of an archived class are hidden from everyone, admins included.
 * Only teachers create exams. view / update / delete / restore all use the same rule.
 */
class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Exam $exam): bool
    {
        return $this->canAccess($user, $exam);
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->canAccess($user, $exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->canAccess($user, $exam);
    }

    public function restore(User $user, Exam $exam): bool
    {
        return $this->canAccess($user, $exam);
    }

    private function canAccess(User $user, Exam $exam): bool
    {
        if (! $user->isAdmin() && $exam->author_id !== $user->id) {
            return false;
        }

        // Archived classes are soft-deleted, so the default scope leaves them out; status is a second guard.
        return ClassAssignment::query()
            ->whereKey($exam->class_assignment_id)
            ->where('status', 'active')
            ->exists();
    }
}
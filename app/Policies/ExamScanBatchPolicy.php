<?php

namespace App\Policies;

use App\Models\ExamScanBatch;
use App\Models\User;

class ExamScanBatchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExamScanBatch $examScanBatch): bool
    {
        return $examScanBatch->set !== null && $user->can('view', $examScanBatch->set);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExamScanBatch $examScanBatch): bool
    {
        return $examScanBatch->set !== null && $user->can('update', $examScanBatch->set);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExamScanBatch $examScanBatch): bool
    {
        return $this->update($user, $examScanBatch);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExamScanBatch $examScanBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExamScanBatch $examScanBatch): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\ClassAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClassAssignmentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClassAssignment $classAssignment): bool
    {
        return $user->teacher && $classAssignment->teacher_id === $user->teacher->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->teacher !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClassAssignment $classAssignment): bool
    {
        return $this->view($user, $classAssignment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClassAssignment $classAssignment): bool
    {
        return $this->view($user, $classAssignment);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClassAssignment $classAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClassAssignment $classAssignment): bool
    {
        return false;
    }
    public function recordAttendance(User $user, ClassAssignment $classAssignment): bool
    {
        return $this->view($user, $classAssignment);
    }
}

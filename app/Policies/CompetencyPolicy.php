<?php

namespace App\Policies;

use App\Models\Competency;
use App\Models\User;

/** Competency lists are personal: only the teacher who made one can see or change it. */
class CompetencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Competency $competency): bool
    {
        return $user->isTeacher() && $competency->owner_id === $user->id;
    }

    public function delete(User $user, Competency $competency): bool
    {
        return $this->update($user, $competency);
    }
}
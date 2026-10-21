<?php

namespace App\Policies;

use App\Models\ExamSet;
use App\Models\User;

/** A set follows its exam. A set of a deleted exam is not accessible. */
class ExamSetPolicy
{
    public function view(User $user, ExamSet $set): bool
    {
        return $set->exam !== null && $user->can('view', $set->exam);
    }

    public function update(User $user, ExamSet $set): bool
    {
        return $set->exam !== null && $user->can('update', $set->exam);
    }

    public function delete(User $user, ExamSet $set): bool
    {
        return $this->update($user, $set);
    }
}
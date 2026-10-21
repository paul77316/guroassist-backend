<?php

namespace App\Policies;

use App\Models\Intervention;
use App\Models\User;

class InterventionPolicy extends AuthoredRecordPolicy
{
    /** Recording a follow-up, reschedule, completion or reopen follows the same rule as editing. */
    public function addEntry(User $user, Intervention $intervention): bool
    {
        return $this->canAccess($user, $intervention);
    }
}

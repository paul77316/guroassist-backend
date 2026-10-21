<?php

namespace App\Policies;

use App\Models\User;

class SetupPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool { return false; }
    public function create(User $user): bool { return false; }
    public function update(User $user): bool { return false; }
    public function delete(User $user): bool { return false; }
}
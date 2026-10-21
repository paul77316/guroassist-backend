<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    /** Admins can do everything, including correcting records in an archived class. */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /** Reading is allowed for the class's teacher, also after the class is archived. */
    public function view(User $user, Assignment $assignment): bool
    {
        return $this->owns($user, $assignment);
    }

    /** Changing anything needs the class's teacher and an active class. */
    public function update(User $user, Assignment $assignment): bool
    {
        return $this->owns($user, $assignment) && $this->classIsActive($assignment);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function recordSubmissions(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    private function owns(User $user, Assignment $assignment): bool
    {
        return $user->teacher !== null
            && $assignment->classAssignment->teacher_id === $user->teacher->id;
    }

    private function classIsActive(Assignment $assignment): bool
    {
        return $assignment->classAssignment->status === 'active';
    }
}

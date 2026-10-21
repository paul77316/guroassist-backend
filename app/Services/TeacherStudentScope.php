<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The "student still in your classes" rule, used by the due list for interventions and communications.
 * It is the same rule InterventionListService applies and StudentPolicy::view uses for teachers: the student
 * must be enrolled in one of the user's own classes. Archived classes are soft-deleted, so they never count.
 * The query must have a student() relation. A user without a teacher profile (an admin) matches nothing.
 */
class TeacherStudentScope
{
    public function apply(Builder $query, User $user): Builder
    {
        $teacher = $user->teacher;
        if (! $teacher) {
            return $query->whereRaw('1 = 0');
        }

        $classIds = $teacher->classAssignments()->pluck('id');

        return $query->whereHas('student', fn ($s) => $s->whereHas(
            'enrollments',
            fn ($e) => $e->whereIn('class_assignment_id', $classIds)
        ));
    }
}

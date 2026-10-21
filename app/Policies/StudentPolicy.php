<?php 
namespace App\Policies; 
use App\Models\Student; 
use App\Models\User; 

class StudentPolicy { 
    /** * Admins can do anything; teachers are checked per-student below. * This runs before every other method — returning true/false here * short-circuits the rest, returning null falls through to the * specific method (viewAny, view, update, etc.) below. */ 
    public function before(User $user, string $ability): ?bool { 
        return $user->isAdmin() ? true : null; 
    } 

    public function viewAny(User $user): bool { 
        // Any authenticated teacher can see A list — the list itself is
        // scoped to their own students inside the controller/query, not here.
        return $user->isTeacher(); 
    }
    public function create(User $user): bool { 
        // Any teacher can add a new student; which section they can assign
        // is validated separately (against their own class_assignments) in
        // the controller when the section_id is processed.
        return $user->isTeacher();
    }

    public function view(User $user, Student $student): bool { 
        return $this->teacherHasStudent($user, $student); 
    } 

    public function update(User $user, Student $student): bool { 
        return $this->teacherHasStudent($user, $student); 
    } 

    public function delete(User $user, Student $student): bool { 
        // "Delete" here means archive (soft delete) — same access rule as update.
        return $this->teacherHasStudent($user, $student); 
    } 

   /** True if the student is enrolled in any of this teacher's classes. */
    protected function teacherHasStudent(User $user, Student $student): bool
    {
        $teacher = $user->teacher;
        if (! $teacher) {
            return false;
        }

        return $student->enrollments()
            ->whereIn('class_assignment_id', $teacher->classAssignments()->pluck('id'))
            ->exists();
    }
}
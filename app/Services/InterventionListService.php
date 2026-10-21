<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The signed-in teacher's own interventions across all of their students, so a teacher can find
 * completed (or active) interventions without opening each student.
 *
 * Same visibility rules as everywhere else in the module:
 *  - only records the user wrote (admins have none of their own, so they get an empty list);
 *  - records of an archived class are left out;
 *  - the student must still be visible to the teacher (enrolled in one of the teacher's active classes),
 *    which is the rule StudentPolicy::view applies, so a listed record can always be opened.
 * Rows carry no free text; the text stays in the detail endpoint.
 */
class InterventionListService
{
    public function paginate(User $user, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Intervention::query()
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->with(['student', 'classAssignment.section.gradeLevel', 'classAssignment.subject'])
            // The date of the latest "completed" history entry (a reopened case may be completed again).
            ->withMax(['followups as completed_on' => fn ($q) => $q->where('entry_type', 'completed')], 'followup_date');

        $teacher = $user->teacher;
        if ($teacher) {
            $classIds = $teacher->classAssignments()->pluck('id'); // archived classes are soft-deleted, so excluded
            $query->whereHas('student', fn ($s) => $s->whereHas(
                'enrollments',
                fn ($e) => $e->whereIn('class_assignment_id', $classIds)
            ));
        } else {
            $query->whereRaw('1 = 0');
        }

        $status = $filters['status'] ?? null;
        if ($status === 'active') {
            $query->active();
        } elseif ($status) {
            $query->where('status', $status);
        }

        if (! empty($filters['class_assignment_id'])) {
            $query->where('class_assignment_id', (int) $filters['class_assignment_id']);
        }
        if (! empty($filters['concern_type'])) {
            $query->where('concern_type', $filters['concern_type']);
        }
        if (! empty($filters['outcome'])) {
            $query->where('outcome', $filters['outcome']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . addcslashes($filters['search'], '%_\\') . '%';
            $query->whereHas('student', fn ($s) => $s->where(
                fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term])
                    ->orWhere('student_id', 'like', $term)
            ));
        }

        // Completed: most recently completed first. Everything else: newest intervention first.
        if ($status === 'completed') {
            $query->orderByDesc('completed_on')->orderByDesc('id');
        } else {
            $query->orderByDesc('intervention_date')->orderByDesc('id');
        }

        return $query->paginate($perPage);
    }
}

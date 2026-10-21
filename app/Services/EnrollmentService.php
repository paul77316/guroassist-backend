<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentGradeLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(private AttendanceService $attendance) {}

    public function roster(ClassAssignment $ca): Collection
    {
        return $this->attendance->roster($ca);
    }

    public function classGradeId(ClassAssignment $ca): int
    {
        return (int) $ca->loadMissing('section')->section->grade_level_id;
    }

    /** A message if the student's grade level for the class's school year differs from the class's, else null. */
    public function levelConflict(Student $student, ClassAssignment $ca): ?string
    {
        $ca->loadMissing('section.gradeLevel');

        $record = StudentGradeLevel::with('gradeLevel')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $ca->academic_year_id)
            ->first();

        if (! $record || (int) $record->grade_level_id === $this->classGradeId($ca)) {
            return null;
        }

        $classGrade = $ca->section->gradeLevel?->name ?? 'a different grade level';

        return "{$student->full_name} is in {$record->gradeLevel->name} this school year and can't join a {$classGrade} class.";
    }

    /** Active students not yet in the class that the user may see (teachers: students in their own classes this year). */
    public function visibleQuery(ClassAssignment $ca, User $user): Builder
    {
        $q = Student::query()
            ->where('status', 'active')
            ->whereDoesntHave('enrollments', fn ($e) => $e->where('class_assignment_id', $ca->id));

        if (! $user->isAdmin()) {
            $mine = $user->teacher?->classAssignments()
                ->where('academic_year_id', $ca->academic_year_id)->pluck('id') ?? collect();
            $q->whereHas('enrollments', fn ($e) => $e->whereIn('class_assignment_id', $mine));
        }

        return $q;
    }

    /** Visible students whose grade level fits this class (students with no record for the year fit any class). */
    public function candidateQuery(ClassAssignment $ca, User $user): Builder
    {
        $gradeId = $this->classGradeId($ca);

        return $this->visibleQuery($ca, $user)->whereDoesntHave('yearLevels', fn ($y) => $y
            ->where('academic_year_id', $ca->academic_year_id)
            ->where('grade_level_id', '!=', $gradeId));
    }

    public function candidates(ClassAssignment $ca, User $user, ?string $search): Collection
    {
        return $this->candidateQuery($ca, $user)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(200)->get();
    }

    /** Add existing students the user is allowed to see. Returns the number newly enrolled. */
    public function enrollVisible(ClassAssignment $ca, array $ids, User $user): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $allowed = $this->candidateQuery($ca, $user)->whereIn('id', $ids)->pluck('id')->all();
        $already = Enrollment::where('class_assignment_id', $ca->id)->whereIn('student_id', $ids)->pluck('student_id')->all();

        if (array_diff($ids, $allowed, $already)) {
            throw ValidationException::withMessages([
                'student_ids' => ["Some students can't be added to this class."],
            ]);
        }

        return $this->enroll($ca, $allowed, $user);
    }

    /** Copy another class's active roster into this one (same academic year and grade level). */
    public function copyFrom(ClassAssignment $target, ClassAssignment $source, User $user): int
    {
        if ($target->id === $source->id || $target->academic_year_id !== $source->academic_year_id) {
            throw ValidationException::withMessages([
                'copy_from_class_assignment_id' => ['Pick a different class from the same academic year.'],
            ]);
        }
        if ($this->classGradeId($target) !== $this->classGradeId($source)) {
            throw ValidationException::withMessages([
                'copy_from_class_assignment_id' => ['You can only copy a roster from a class of the same grade level.'],
            ]);
        }

        return $this->enroll($target, $this->roster($source)->pluck('id')->all(), $user);
    }

    /** Low-level: no per-user visibility check (callers do that), but the grade level rule always applies. */
    public function enroll(ClassAssignment $ca, array $studentIds, User $user): int
    {
        if ($ca->status !== 'active') {
            throw ValidationException::withMessages(['class' => ['This class is archived.']]);
        }

        $ids = array_values(array_unique(array_map('intval', $studentIds)));
        $students = Student::whereIn('id', $ids)->where('status', 'active')->get()->keyBy('id');
        if (array_diff($ids, $students->keys()->all())) {
            throw ValidationException::withMessages(['student_ids' => ['Some students were not found or are not active.']]);
        }

        $already = Enrollment::where('class_assignment_id', $ca->id)->whereIn('student_id', $ids)->pluck('student_id')->all();
        $toAdd = array_values(array_diff($ids, $already));

        $conflicts = [];
        foreach ($toAdd as $sid) {
            if ($msg = $this->levelConflict($students[$sid], $ca)) {
                $conflicts[] = $msg;
            }
        }
        if ($conflicts) {
            throw ValidationException::withMessages(['student_ids' => $conflicts]);
        }

        $gradeId = $this->classGradeId($ca);

        DB::transaction(function () use ($toAdd, $ca, $user, $gradeId) {
            foreach ($toAdd as $sid) {
                // The first class a student joins in a school year sets their grade level for that year.
                StudentGradeLevel::firstOrCreate(
                    ['student_id' => $sid, 'academic_year_id' => $ca->academic_year_id],
                    ['grade_level_id' => $gradeId]
                );

                $e = Enrollment::create([
                    'student_id' => $sid,
                    'class_assignment_id' => $ca->id,
                    'section_id' => $ca->section_id,
                    'academic_year_id' => $ca->academic_year_id,
                ]);
                AuditLogger::log($user, $e, 'created', null, ['student_id' => $sid, 'class_assignment_id' => $ca->id]);
            }
        });

        return count($toAdd);
    }

    /** Removes the link only. The student and their attendance history stay. */
    public function remove(ClassAssignment $ca, Student $student, User $user): void
    {
        $e = Enrollment::where('class_assignment_id', $ca->id)->where('student_id', $student->id)->first();
        if (! $e) {
            throw ValidationException::withMessages(['student' => ['This student is not enrolled in this class.']]);
        }

        DB::transaction(function () use ($e, $user) {
            AuditLogger::log($user, $e, 'deleted',
                ['student_id' => $e->student_id, 'class_assignment_id' => $e->class_assignment_id], null);
            $e->delete();
        });
    }
}
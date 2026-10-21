<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\ClassAssignment;
use App\Models\Student;
use App\Services\AttendanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Full details of one student, plus attendance for each class the viewer is allowed to see. */
class StudentProfileController extends Controller
{
    public function show(Request $request, Student $student, AttendanceSummaryService $attendance)
    {
        $this->authorize('view', $student); // admin, or a teacher who has this student in a class

        $user = $request->user();
        $tz = config('monitoring.timezone');
        $student->load(['yearLevels.gradeLevel', 'sections.gradeLevel']);

        // Teachers only see their own classes; another teacher's class stays private.
        $classes = ClassAssignment::query()
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id))
            ->when(! $user->isAdmin(), fn ($q) => $q->where('teacher_id', $user->teacher?->id))
            ->with(['section.gradeLevel', 'subject', 'academicYear', 'teacher.user'])
            ->orderByDesc('academic_year_id')
            ->get();

        $today = Carbon::now($tz)->toDateString();

        $classData = $classes->map(function (ClassAssignment $ca) use ($attendance, $student, $today, $user) {
            // From the start of that school year (at most one year back) to today.
            $start = Carbon::parse($ca->academicYear->start_date)->toDateString();
            $from = $start <= $today ? $start : $today;
            if (Carbon::parse($from)->diffInDays(Carbon::parse($today)) > 366) {
                $from = Carbon::parse($today)->subDays(366)->toDateString();
            }

            $history = $attendance->studentHistory($ca, $student, $from, $today);
            $stats = $history['stats'];

            return [
                'id' => $ca->id,
                'label' => "{$ca->section->gradeLevel->name} - {$ca->section->name}",
                'subject' => $ca->subject->name,
                'academic_year' => $ca->academicYear->name,
                'teacher' => $user->isAdmin() ? $ca->teacher?->user?->name : null,
                'attendance' => [
                    'period' => $history['period'],
                    'present' => $stats['present'],
                    'late' => $stats['late'],
                    'absent' => $stats['absent'],
                    'excused' => $stats['excused'],
                    'days_counted' => $stats['days_counted'],
                    'attendance_rate' => $stats['attendance_rate'],
                    'consecutive_absences' => $stats['consecutive_absences'],
                    'needs_review' => $stats['needs_review'],
                    'label' => $stats['label'],
                    'reasons' => $stats['reasons'],
                    'unmarked_days' => $history['unmarked_dates']->count(),
                ],
            ];
        })->values();

        $profile = (new StudentResource($student))->resolve($request);
        $profile['age'] = $student->date_of_birth
            ? (int) $student->date_of_birth->diffInYears(Carbon::now($tz))
            : null;
        $profile['classes'] = $classData;

        return response()->json(['data' => $profile]);
    }
}

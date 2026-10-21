<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassAssignment;
use App\Models\MonitoringSetting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Attendance numbers and review flags. The dashboard, student profile and reports
 * should call this rather than recompute anything.
 */
class AttendanceSummaryService
{
    public function __construct(private AttendanceService $attendance) {}

    /** The teacher's thresholds, falling back to the defaults in config/monitoring.php. */
    public function settingsForTeacher(?int $teacherId): array
    {
        $row = $teacherId ? MonitoringSetting::where('teacher_id', $teacherId)->first() : null;

        return [
            'attendance_rate_threshold' => (float) ($row?->attendance_rate_threshold ?? config('monitoring.attendance_rate_threshold')),
            'consecutive_absence_threshold' => (int) ($row?->consecutive_absence_threshold ?? config('monitoring.consecutive_absence_threshold')),
            'min_days_for_rate' => (int) ($row?->min_days_for_rate ?? config('monitoring.min_days_for_rate')),
            'missing_assignments_threshold' => (int) ($row?->missing_assignments_threshold ?? config('monitoring.missing_assignments_threshold')),
            'guardian_email_enabled' => (bool) ($row?->guardian_email_enabled ?? false),
            'notify_on_absent' => (bool) ($row?->notify_on_absent ?? true),
            'notify_on_late' => (bool) ($row?->notify_on_late ?? false),
        ];
    }

    public function summary(ClassAssignment $ca, string $from, string $to): array
    {
        $cfg = $this->settingsForTeacher($ca->teacher_id);
        $roster = $this->attendance->roster($ca);

        $records = Attendance::where('class_assignment_id', $ca->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->get(['student_id', 'attendance_date', 'status'])
            ->map(fn ($r) => [
                'student_id' => $r->student_id,
                'date' => Carbon::parse($r->attendance_date)->format('Y-m-d'),
                'status' => $r->status,
            ]);

        $classDates = $records->pluck('date')->unique()->sort()->values();
        $byStudent = $records->groupBy('student_id');

        $students = $roster
            ->map(fn ($s) => $this->student($s, $byStudent->get($s->id, collect()), $classDates, $cfg))
            ->values();

        $attended = $students->sum(fn ($r) => $r['present'] + $r['late']);
        $counted = $students->sum('days_counted');

        return [
            'period' => ['from' => $from, 'to' => $to],
            'settings' => $cfg,
            'class_days' => $classDates->count(),
            'students' => $students,
            'summary' => [
                'student_count' => $students->count(),
                'class_rate' => $counted > 0 ? round($attended / $counted * 100, 1) : null,
                'needs_review_count' => $students->where('needs_review', true)->count(),
            ],
        ];
    }

    /** One student's attendance for a class: counts, reasons, and the date of every mark. */
    public function studentHistory(ClassAssignment $ca, Student $student, string $from, string $to): array
    {
        $cfg = $this->settingsForTeacher($ca->teacher_id);

        $classDates = Attendance::where('class_assignment_id', $ca->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->pluck('attendance_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()->sort()->values();

        $records = Attendance::where('class_assignment_id', $ca->id)
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->orderBy('attendance_date')
            ->get(['attendance_date', 'status', 'remarks'])
            ->map(fn ($r) => [
                'date' => Carbon::parse($r->attendance_date)->format('Y-m-d'),
                'status' => $r->status,
                'remarks' => $r->remarks,
            ])
            ->values();

        $mine = $records->map(fn ($r) => ['student_id' => $student->id, 'date' => $r['date'], 'status' => $r['status']]);

        return [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_id,
                'full_name' => $student->full_name,
            ],
            'period' => ['from' => $from, 'to' => $to],
            'settings' => $cfg,
            'stats' => $this->student($student, $mine, $classDates, $cfg),
            'records' => $records,
            // class days on which attendance was taken but this student has no mark
            'unmarked_dates' => $classDates->diff($records->pluck('date'))->values(),
        ];
    }

    private function student($s, Collection $mine, Collection $classDates, array $cfg): array
    {
        $count = fn (string $status) => $mine->where('status', $status)->count();
        $present = $count('present');
        $absent = $count('absent');
        $late = $count('late');
        $excused = $count('excused');

        // Excused days are left out of the rate; unmarked days never count.
        $counted = $present + $absent + $late;
        $rate = $counted > 0 ? round(($present + $late) / $counted * 100, 1) : null;

        // Run of absences ending at the most recent class day this student was marked.
        $byDate = $mine->pluck('status', 'date');
        $consecutive = 0;
        foreach ($classDates->reverse() as $date) {
            $status = $byDate->get($date);
            if ($status === null) continue;      // unmarked: skip
            if ($status !== 'absent') break;     // present, late or excused ends the run
            $consecutive++;
        }

        $reasons = [];
        if ($rate !== null && $counted >= $cfg['min_days_for_rate'] && $rate < $cfg['attendance_rate_threshold']) {
            $reasons[] = "Attendance rate is {$rate}% over {$counted} recorded days (review threshold {$cfg['attendance_rate_threshold']}%).";
        }
        if ($consecutive >= $cfg['consecutive_absence_threshold']) {
            $reasons[] = "{$consecutive} consecutive absences.";
        }

        return [
            'student_id' => $s->id,
            'student_number' => $s->student_id,
            'full_name' => $s->full_name,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'days_counted' => $counted,
            'attendance_rate' => $rate,
            'consecutive_absences' => $consecutive,
            'needs_review' => count($reasons) > 0,
            'label' => count($reasons) > 0 ? 'Attendance concern' : null,
            'reasons' => $reasons,
        ];
    }
}

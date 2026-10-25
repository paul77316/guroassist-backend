<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassAssignment;
use App\Models\Student;
use Illuminate\Support\Collection;

class AttendanceService
{
    /** Active students enrolled in this class. */
    public function roster(ClassAssignment $ca): Collection
    {
        return Student::query()
            ->where('status', 'active')
            ->whereHas('enrollments', fn ($q) => $q->where('class_assignment_id', $ca->id))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /** Roster plus whatever status was already saved for the given date. */
    public function rosterWithStatus(ClassAssignment $ca, string $date): Collection
    {
        $marked = Attendance::where('class_assignment_id', $ca->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        return $this->roster($ca)->map(fn (Student $s) => [
            'student_id' => $s->id,
            'full_name' => $s->full_name,
            'status' => $marked[$s->id]->status ?? null,
            'remarks' => $marked[$s->id]->remarks ?? null,
            'version' => $marked[$s->id]->sync_version ?? 0,
        ])->values();
    }
}

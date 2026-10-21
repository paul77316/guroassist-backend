<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassAssignment;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        ])->values();
    }

    /** Insert/update marked students, delete records for students sent with a null status. */
    public function saveBulk(ClassAssignment $ca, string $date, array $records, int $userId): int
    {
        $rosterIds = $this->roster($ca)->pluck('id')->all();
        $submitted = array_column($records, 'student_id');

        if (array_diff($submitted, $rosterIds)) {
            throw ValidationException::withMessages([
                'records' => ['Some students are not enrolled in this class.'],
            ]);
        }

        $toSave = array_filter($records, fn ($r) => ! empty($r['status']));
        $clearIds = array_column(
            array_filter($records, fn ($r) => empty($r['status'])),
            'student_id'
        );

        $now = now();
        $rows = array_map(fn ($r) => [
            'student_id' => $r['student_id'],
            'class_assignment_id' => $ca->id,
            'attendance_date' => $date,
            'status' => $r['status'],
            'remarks' => $r['remarks'] ?? null,
            'recorded_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($toSave));

        DB::transaction(function () use ($rows, $clearIds, $ca, $date) {
            if ($rows) {
                Attendance::upsert(
                    $rows,
                    ['student_id', 'class_assignment_id', 'attendance_date'],
                    ['status', 'remarks', 'recorded_by', 'updated_at']
                );
            }

            if ($clearIds) {
                Attendance::where('class_assignment_id', $ca->id)
                    ->whereDate('attendance_date', $date)
                    ->whereIn('student_id', $clearIds)
                    ->delete();
            }
        });

        // Guardian emails must never stop attendance from saving.
        try {
            app(GuardianNotificationService::class)->queueForSave($ca, $date, $records);
        } catch (\Throwable $e) {
            report($e);
        }

        return count($rows) + count($clearIds);
    }
}

<?php

namespace App\Services;

use App\Jobs\SendGuardianAttendanceNotice;
use App\Models\AttendanceNotification;
use App\Models\ClassAssignment;
use App\Models\MonitoringSetting;
use App\Models\Student;
use Carbon\Carbon;

/** Decides which guardians get an attendance email after attendance is saved. */
class GuardianNotificationService
{
    /**
     * @param array $records [{student_id, status}] as saved
     * @return array{enabled:bool, queued:int, no_email:int, too_old:bool}
     */
    public function queueForSave(ClassAssignment $ca, string $date, array $records): array
    {
        $result = ['enabled' => false, 'queued' => 0, 'no_email' => 0, 'too_old' => false];

        $cfg = MonitoringSetting::where('teacher_id', $ca->teacher_id)->first();
        if (! $cfg || ! $cfg->guardian_email_enabled) {
            return $result;
        }
        $result['enabled'] = true;

        $statuses = array_values(array_filter([
            $cfg->notify_on_absent ? 'absent' : null,
            $cfg->notify_on_late ? 'late' : null,
        ]));
        if (! $statuses) {
            return $result;
        }

        // Only recent dates: back-filling old attendance must not email guardians about last month.
        $tz = config('monitoring.timezone');
        $today = Carbon::now($tz)->startOfDay();
        $day = Carbon::parse($date, $tz)->startOfDay();
        $maxAge = (int) config('monitoring.guardian_email_max_age_days', 2);
        if ($day->gt($today) || (int) $day->diffInDays($today) > $maxAge) {
            $result['too_old'] = true;

            return $result;
        }

        $targets = collect($records)
            ->filter(fn ($r) => in_array($r['status'] ?? null, $statuses, true))
            ->keyBy('student_id');
        if ($targets->isEmpty()) {
            return $result;
        }

        $students = Student::whereIn('id', $targets->keys())->get()->keyBy('id');
        $delay = (int) config('monitoring.guardian_email_delay_minutes', 5);

        foreach ($targets as $studentId => $record) {
            $student = $students->get($studentId);
            if (! $student || ! $student->guardian_email) {
                $result['no_email']++;
                continue;
            }

            $notice = AttendanceNotification::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'class_assignment_id' => $ca->id,
                    'attendance_date' => $date,
                    'status' => $record['status'],
                ],
                ['state' => 'queued']
            );

            // New notice, or one that was skipped earlier because the mark was changed back and forth.
            if ($notice->wasRecentlyCreated || $notice->state === 'skipped') {
                $notice->update(['state' => 'queued', 'error' => null]);
                SendGuardianAttendanceNotice::dispatch($notice->id)->delay(now()->addMinutes($delay));
                $result['queued']++;
            }
        }

        return $result;
    }
}

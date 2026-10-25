<?php

namespace App\Services;

use App\Models\AttendanceSyncOperation;
use App\Models\ClassAssignment;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * In-app reminders: counts only, calculated on request, never stored.
 * No names, no text, neutral wording. Off until the teacher opts in; admins have no reminders.
 * The counting itself lives in TodaySummaryService (single source of truth, shared with the dashboard panel),
 * so every visibility rule applies exactly as there.
 */
class NotificationSummaryService
{
    public function __construct(
        private TodaySummaryService $today,
        private StudentRecordGuard $guard,
    ) {}

    /** @return array{in_app_enabled: bool, include_students_to_review: bool} */
    public function preferencesFor(User $user): array
    {
        $teacher = $user->teacher;
        $row = $teacher ? NotificationPreference::where('teacher_id', $teacher->id)->first() : null;

        return [
            'in_app_enabled' => (bool) ($row?->in_app_enabled ?? false),
            'include_students_to_review' => (bool) ($row?->include_students_to_review ?? false),
        ];
    }

    /** Saves both switches. Writes (and audits) only when something changed. */
    public function savePreferences(User $user, array $data): array
    {
        $teacher = $user->teacher;
        $new = [
            'in_app_enabled' => (bool) $data['in_app_enabled'],
            'include_students_to_review' => (bool) $data['include_students_to_review'],
        ];

        return DB::transaction(function () use ($user, $teacher, $new) {
            $row = NotificationPreference::firstOrNew(['teacher_id' => $teacher->id]);
            $existed = $row->exists;
            $old = $existed ? $this->flags($row) : null;

            if ($existed && $old === $new) {
                return $new;
            }

            $row->in_app_enabled = $new['in_app_enabled'];
            $row->include_students_to_review = $new['include_students_to_review'];
            $row->save();

            AuditLogger::log($user, $row, $existed ? 'updated' : 'created', $old, $new);

            return $new;
        });
    }

    /**
     * What the bell shows. When the switch is off (or for an admin) this is only ['enabled' => false]
     * and nothing else is queried.
     */
    public function summary(User $user): array
    {
        $prefs = $this->preferencesFor($user);
        if (! $user->teacher || ! $prefs['in_app_enabled']) {
            return ['enabled' => false];
        }

        $today = $this->guard->today();
        $timezone = config('monitoring.timezone');
        $dayStartsAt = Carbon::parse($today, $timezone)->startOfDay()->utc();
        $nextDayStartsAt = $dayStartsAt->copy()->addDay();
        $due = $this->today->followUpsDue($user, $today);
        $review = $prefs['include_students_to_review'] ? $this->today->studentsToReview($user) : null;
        $syncedMarks = AttendanceSyncOperation::query()
            ->where('user_id', $user->id)
            ->where('outcome', 'applied')
            ->where('updated_at', '>=', $dayStartsAt)
            ->where('updated_at', '<', $nextDayStartsAt)
            ->select('class_assignment_id', 'student_id', 'attendance_date')
            ->groupBy('class_assignment_id', 'student_id', 'attendance_date');
        $mobileAttendanceSynced = DB::query()->fromSub($syncedMarks, 'synced_marks')->count();
        $mobileAttendanceSyncs = AttendanceSyncOperation::query()
            ->where('user_id', $user->id)
            ->where('outcome', 'applied')
            ->where('updated_at', '>=', $dayStartsAt)
            ->where('updated_at', '<', $nextDayStartsAt)
            ->selectRaw('class_assignment_id, attendance_date, COUNT(DISTINCT student_id) as marks_count')
            ->groupBy('class_assignment_id', 'attendance_date')
            ->orderBy('attendance_date')
            ->get();
        $classes = ClassAssignment::query()
            ->with(['section.gradeLevel', 'subject'])
            ->whereIn('id', $mobileAttendanceSyncs->pluck('class_assignment_id')->unique())
            ->get()
            ->keyBy('id');
        $mobileAttendanceSyncs = $mobileAttendanceSyncs->map(function ($sync) use ($classes) {
            $class = $classes->get($sync->class_assignment_id);
            $classLabel = $class
                ? ($class->section?->gradeLevel?->name ?? '').' - '.($class->section?->name ?? '').' ('.($class->subject?->name ?? '').')'
                : 'Class';

            return [
                'class' => $classLabel,
                'attendance_date' => Carbon::parse($sync->attendance_date)->toDateString(),
                'marks_count' => (int) $sync->marks_count,
            ];
        })->values();

        return [
            'enabled' => true,
            'today' => $today,
            'follow_ups_due' => $due,
            'students_to_review' => $review,
            'mobile_attendance_synced' => $mobileAttendanceSynced,
            'mobile_attendance_syncs' => $mobileAttendanceSyncs,
            'badge_count' => $due['total'] + ($review ?? 0) + $mobileAttendanceSynced,
        ];
    }

    private function flags(NotificationPreference $row): array
    {
        return [
            'in_app_enabled' => (bool) $row->in_app_enabled,
            'include_students_to_review' => (bool) $row->include_students_to_review,
        ];
    }
}

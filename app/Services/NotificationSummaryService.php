<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
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
        $due = $this->today->followUpsDue($user, $today);
        $review = $prefs['include_students_to_review'] ? $this->today->studentsToReview($user) : null;

        return [
            'enabled' => true,
            'today' => $today,
            'follow_ups_due' => $due,
            'students_to_review' => $review,
            'badge_count' => $due['total'] + ($review ?? 0),
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

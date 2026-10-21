<?php

namespace App\Services;

use App\Models\Intervention;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The dashboard "Today" counts. Counts only: no names, no text, no stored data, neutral wording.
 * Single source of truth: the bell (NotificationSummaryService) delegates here, so both always agree.
 * Reuses InterventionService::due, ParentCommunicationService::due and StudentsToReviewService.
 * Admins have no teacher profile: summary() answers only ['applies' => false] and queries nothing.
 */
class TodaySummaryService
{
    public function __construct(
        private InterventionService $interventions,
        private ParentCommunicationService $communications,
        private StudentsToReviewService $review,
        private StudentRecordGuard $guard,
    ) {}

    public function summary(User $user): array
    {
        if (! $user->teacher) {
            return ['applies' => false];
        }

        $today = $this->guard->today();
        $cacheKey = "dashboard_summary_{$user->id}_{$today}";

        return Cache::remember($cacheKey, 300, function () use ($user, $today) {
            return [
                'applies' => true,
                'today' => $today,
                'follow_ups_due' => $this->followUpsDue($user, $today),
                'students_to_review' => $this->studentsToReview($user),
                'active_interventions' => $this->activeInterventions($user, $today),
            ];
        });
    }

    /** @return array{interventions: int, parent_contacts: int, total: int, overdue: int} */
    public function followUpsDue(User $user, string $today): array
    {
        $interventions = $this->interventions->due($user, $today);
        $contacts = $this->communications->due($user, $today);

        $isOverdue = fn ($row) => $row->follow_up_date->format('Y-m-d') < $today;

        return [
            'interventions' => $interventions->count(),
            'parent_contacts' => $contacts->count(),
            'total' => $interventions->count() + $contacts->count(),
            'overdue' => $interventions->filter($isOverdue)->count() + $contacts->filter($isOverdue)->count(),
        ];
    }

    /** Students with at least one prompt not handled by a recent completed intervention. HEAVY call. */
    public function studentsToReview(User $user): int
    {
        return count(array_filter(
            $this->review->forUser($user)['students'],
            fn ($s) => $s['needs_attention']
        ));
    }

    /**
     * Open interventions (planned, in progress, requires follow-up), same scopes as
     * StudentsToReviewService::activeInterventions, but counted per intervention.
     *
     * @return array{students: int, interventions: int, with_follow_up_due: int}
     */
    public function activeInterventions(User $user, string $today): array
    {
        $rows = Intervention::query()
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->active()
            ->get(['id', 'student_id', 'follow_up_date', 'status']);

        return [
            'students' => $rows->pluck('student_id')->unique()->count(),
            'interventions' => $rows->count(),
            'with_follow_up_due' => $rows->filter(
                fn ($i) => $i->follow_up_date && $i->follow_up_date->format('Y-m-d') <= $today
            )->count(),
        ];
    }
}

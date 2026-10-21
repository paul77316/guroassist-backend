<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\Intervention;
use App\Models\InterventionFollowup;
use App\Models\User;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * One list of the teacher's students who currently match a review prompt, combining the existing
 * "Attendance concern" and "Missing work" prompts across all of the teacher's active classes.
 * It recomputes nothing: AttendanceSummaryService and AssignmentService::missing decide the prompts,
 * using the teacher's own thresholds. A prompt is a reason to look, never a verdict.
 * Nothing is created here; the teacher starts an intervention herself.
 *
 * A prompt is "handled" when the teacher completed an intervention for the same concern type
 * (in the same class, or with no class) within the last HANDLED_DAYS days. This is calculated from the
 * interventions every time and never stored. Handled prompts are still returned (marked), so the screen can
 * hide them by default and show them on request. A student "needs attention" while any of their prompts is unhandled.
 */
class StudentsToReviewService
{
    /** Attendance is looked at over the last 30 days, like the Attendance report's default. */
    private const DAYS = 30;

    /** How long a completed intervention keeps its prompt handled. After that the prompt returns if the data still triggers it. */
    private const HANDLED_DAYS = 30;

    /** Completed interventions with these outcomes handle a prompt. Remove 'no_change' to keep those prompts visible. */
    private const HANDLED_OUTCOMES = ['improved', 'partly_improved', 'no_change', 'not_yet_known'];

    public function __construct(
        private AttendanceSummaryService $attendance,
        private AssignmentService $assignments,
        private StudentRecordGuard $guard,
    ) {}

    /**
     * @return array{students: array, classes: array, period: array, today: string, handled_days: int}
     */
    public function forUser(User $user): array
    {
        $today = $this->guard->today();
        $from = Carbon::parse($today)->subDays(self::DAYS - 1)->toDateString();
        $period = ['from' => $from, 'to' => $today];

        $teacher = $user->teacher;
        if (! $teacher) {
            return ['students' => [], 'classes' => [], 'period' => $period, 'today' => $today, 'handled_days' => self::HANDLED_DAYS];
        }

        // Archived classes are soft-deleted, so they are left out; status is a second guard.
        $classes = ClassAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->with(['section.gradeLevel', 'subject'])
            ->get();

        $classList = [];
        $rows = [];

        foreach ($classes as $ca) {
            $info = [
                'id' => $ca->id,
                'label' => trim(($ca->section?->gradeLevel?->name ? $ca->section->gradeLevel->name . ' - ' : '') . $ca->section?->name),
                'subject' => $ca->subject?->name,
            ];
            $classList[] = $info;

            $summary = $this->attendance->summary($ca, $from, $today);
            $missing = collect($this->assignments->missing($ca)['students'])->keyBy('student_id');

            foreach ($summary['students'] as $s) {
                $id = $s['student_id'];
                $rows[$id] ??= [
                    'student_id' => $id,
                    'student_number' => $s['student_number'],
                    'full_name' => $s['full_name'],
                    'classes' => [],
                    'flags' => [],
                ];
                $rows[$id]['classes'][] = $info;

                if ($s['needs_review']) {
                    $rows[$id]['flags'][] = [
                        'type' => 'attendance_concern', 'label' => $s['label'], 'class' => $info, 'reasons' => $s['reasons'],
                    ];
                }

                $m = $missing->get($id);
                if ($m && $m['needs_review']) {
                    $rows[$id]['flags'][] = [
                        'type' => 'missing_work', 'label' => $m['label'], 'class' => $info, 'reasons' => $m['reasons'],
                    ];
                }
            }
        }

        $flagged = array_filter($rows, fn ($r) => $r['flags'] !== []);

        // The teacher's own open interventions ("Intervention active") and completed ones that handle a prompt.
        $active = $flagged === [] ? [] : $this->activeByStudent($user, $today);
        $handled = $this->handledByStudent($user, array_keys($flagged), $today);
        $none = ['active_count' => 0, 'follow_up_due' => false];

        $students = array_map(function ($r) use ($active, $none, $handled) {
            $mine = $handled[$r['student_id']] ?? collect();
            $r['flags'] = array_map(fn ($f) => $f + ['handled' => $this->handledBy($mine, $f)], $r['flags']);
            $r['needs_attention'] = count(array_filter($r['flags'], fn ($f) => $f['handled'] === null)) > 0;
            $r['intervention'] = $active[$r['student_id']] ?? $none;

            return $r;
        }, array_values($flagged));

        // Most unhandled prompts first, then most prompts, then name.
        $open = fn ($r) => count(array_filter($r['flags'], fn ($f) => $f['handled'] === null));
        usort($students, fn ($a, $b) => [$open($b), count($b['flags']), $a['full_name']] <=> [$open($a), count($a['flags']), $b['full_name']]);

        return [
            'students' => $students, 'classes' => $classList, 'period' => $period,
            'today' => $today, 'handled_days' => self::HANDLED_DAYS,
        ];
    }

    /**
     * The user's own open interventions (Planned, In Progress, Requires Follow-up), by student.
     * Used for the "Intervention active" indicator on the review list and on the two reports.
     * Records of an archived class are left out. Counts and a due flag only, no text.
     *
     * @return array<int, array{active_count: int, follow_up_due: bool}>
     */
    public function activeInterventions(User $user): array
    {
        return $this->activeByStudent($user, $this->guard->today());
    }

    private function activeByStudent(User $user, string $today): array
    {
        return Intervention::query()
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->active()
            ->get(['id', 'student_id', 'follow_up_date', 'status'])
            ->groupBy('student_id')
            ->map(fn ($mine) => [
                'active_count' => $mine->count(),
                'follow_up_due' => $mine->contains(
                    fn ($i) => $i->follow_up_date && $i->follow_up_date->format('Y-m-d') <= $today
                ),
            ])
            ->all();
    }

    /**
     * The user's own completed interventions for these students that are recent enough to handle a prompt,
     * by student id. One query however many students there are. A reopened intervention is no longer
     * Completed, so it stops handling anything. Ids, dates and outcome keys only; no free text is selected.
     *
     * @param  int[]  $studentIds
     * @return array<int, Collection>
     */
    private function handledByStudent(User $user, array $studentIds, string $today): array
    {
        if ($studentIds === []) {
            return [];
        }

        $since = Carbon::parse($today)->subDays(self::HANDLED_DAYS)->toDateString();

        return Intervention::query()
            ->authoredBy($user->id)
            ->inActiveClasses()
            ->where('status', InterventionStatus::COMPLETED)
            ->whereIn('concern_type', ['attendance_concern', 'missing_work'])
            ->whereIn('student_id', $studentIds)
            ->select(['id', 'student_id', 'class_assignment_id', 'concern_type', 'outcome'])
            // The date of the latest "completed" history entry (a reopened case may be completed again).
            ->withMax(['followups as completed_on' => fn ($q) => $q->where('entry_type', InterventionFollowup::TYPE_COMPLETED)], 'followup_date')
            ->get()
            ->filter(fn ($i) => $i->completed_on !== null
                && substr((string) $i->completed_on, 0, 10) >= $since
                && in_array($i->outcome, self::HANDLED_OUTCOMES, true))
            ->groupBy('student_id')
            ->all();
    }

    /** The most recent completed intervention for the same concern type and class (or with no class), if any. */
    private function handledBy(Collection $mine, array $flag): ?array
    {
        $hit = $mine
            ->filter(fn ($i) => $i->concern_type === $flag['type']
                && ($i->class_assignment_id === null || (int) $i->class_assignment_id === (int) $flag['class']['id']))
            ->sortByDesc(fn ($i) => substr((string) $i->completed_on, 0, 10))
            ->first();

        if (! $hit) {
            return null;
        }

        return [
            'completed_on' => substr((string) $hit->completed_on, 0, 10),
            'outcome' => $hit->outcome,
            'outcome_label' => InterventionVocabulary::label('OUTCOMES', $hit->outcome),
        ];
    }
}

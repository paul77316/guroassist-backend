<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Assignment and submission tracking. Operational tracking only: nothing here is an official grade.
 * Statuses are decided by App\Support\AssignmentStatus; only submitted | late | excused are stored.
 */
class AssignmentService
{
    public function __construct(
        private AttendanceService $attendance,
        private AttendanceSummaryService $settings,
    ) {}

    /** "Today" in the school timezone (config monitoring.timezone, set with SCHOOL_TIMEZONE). */
    public function today(): string
    {
        return Carbon::now(config('monitoring.timezone'))->toDateString();
    }

    // ---------------------------------------------------------------- assignments

    public function create(ClassAssignment $ca, array $data, User $user): Assignment
    {
        if ($ca->status !== 'active') {
            throw ValidationException::withMessages(['class_assignment_id' => ['This class is archived. New assignments cannot be added.']]);
        }

        return DB::transaction(function () use ($ca, $data, $user) {
            $a = Assignment::create([
                'class_assignment_id' => $ca->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'assigned_date' => $data['assigned_date'],
                'due_date' => $data['due_date'],
                'created_by' => $user->id,
            ]);
            AuditLogger::log($user, $a, 'created', null, $this->values($a));

            return $a;
        });
    }

    /**
     * Edit an assignment. Missing / not-yet are derived, so they follow the new dates automatically;
     * stored submitted/late records are recalculated against the new due date here.
     *
     * @return array{assignment: Assignment, recalculated: int}
     */
    public function update(Assignment $a, array $data, User $user): array
    {
        $earliest = $a->submissions()->whereNotNull('submitted_date')->min('submitted_date');
        if ($earliest && substr((string) $earliest, 0, 10) < $data['assigned_date']) {
            throw ValidationException::withMessages([
                'assigned_date' => ['Some submissions are dated before this date assigned. Correct those first.'],
            ]);
        }

        $old = $this->values($a);
        $dueChanged = $a->due_date->format('Y-m-d') !== $data['due_date'];

        return DB::transaction(function () use ($a, $data, $user, $old, $dueChanged) {
            $a->update(Arr::only($data, ['title', 'description', 'assigned_date', 'due_date']));
            $a->refresh();

            $recalculated = $dueChanged ? $this->recalculate($a) : 0;

            AuditLogger::log($user, $a, 'updated', $old,
                $this->values($a) + ($recalculated ? ['recalculated_submissions' => $recalculated] : []));

            return ['assignment' => $a, 'recalculated' => $recalculated];
        });
    }

    /** Soft delete: the tracking records are kept and can be restored. */
    public function delete(Assignment $a, User $user): void
    {
        DB::transaction(function () use ($a, $user) {
            AuditLogger::log($user, $a, 'deleted', $this->values($a), null);
            $a->delete();
        });
    }

    public function restore(Assignment $a, User $user): void
    {
        DB::transaction(function () use ($a, $user) {
            $a->restore();
            AuditLogger::log($user, $a, 'restored', null, $this->values($a));
        });
    }

    // ---------------------------------------------------------------- reading

    /**
     * Assignments of a class with their counts, by due date (newest first).
     *
     * @return array<int, array{assignment: Assignment, stats: array}>
     */
    public function listForClass(ClassAssignment $ca, string $scope = 'all', ?string $from = null, ?string $to = null, bool $trashedOnly = false): array
    {
        $today = $this->today();

        $assignments = ($trashedOnly ? Assignment::onlyTrashed() : Assignment::query())
            ->where('class_assignment_id', $ca->id)
            ->when($scope === 'upcoming', fn ($q) => $q->whereDate('due_date', '>=', $today))
            ->when($scope === 'past', fn ($q) => $q->whereDate('due_date', '<', $today))
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->orderByDesc('due_date')->orderByDesc('id')
            ->get();

        [$roster, $enrolledOn] = $this->context($ca);
        $subs = AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereIn('student_id', $roster->pluck('id'))
            ->get()->groupBy('assignment_id');

        return $assignments->map(fn (Assignment $a) => [
            'assignment' => $a,
            'stats' => $this->statsFrom($this->statusMap(
                $a, $roster, $enrolledOn, $subs->get($a->id, collect())->keyBy('student_id'), $today
            )),
        ])->all();
    }

    /** Every student of the class with their status. The only place the private feedback is read. */
    public function grid(Assignment $a): array
    {
        [$roster, $enrolledOn] = $this->context($a->classAssignment);
        $subs = $a->submissions()->whereIn('student_id', $roster->pluck('id'))->get()->keyBy('student_id');
        $statuses = $this->statusMap($a, $roster, $enrolledOn, $subs, $this->today());

        $students = $roster->map(function ($s) use ($statuses, $subs) {
            $sub = $subs->get($s->id);

            return [
                'student_id' => $s->id,
                'student_number' => $s->student_id,
                'full_name' => $s->full_name,
                'status' => $statuses[$s->id],
                'recorded' => $sub !== null,
                'submitted_date' => $sub?->submitted_date?->format('Y-m-d'),
                'feedback' => $sub?->feedback,
            ];
        })->values();

        return ['students' => $students, 'stats' => $this->statsFrom($statuses)];
    }

    /**
     * Students with overdue work and no submission. Counts only assignments that are not deleted,
     * are past due, and have no record. Late work counts as handed in; excused work and assignments
     * entered before the student was added to the class are not counted.
     */
    public function missing(ClassAssignment $ca): array
    {
        $today = $this->today();
        $threshold = $this->settings->settingsForTeacher($ca->teacher_id)['missing_assignments_threshold'];

        $due = Assignment::where('class_assignment_id', $ca->id)
            ->whereDate('due_date', '<', $today)->orderBy('due_date')->get();

        [$roster, $enrolledOn] = $this->context($ca);
        $subs = AssignmentSubmission::whereIn('assignment_id', $due->pluck('id'))
            ->whereIn('student_id', $roster->pluck('id'))->get()->groupBy('student_id');

        $students = $roster->map(function ($s) use ($due, $subs, $enrolledOn, $today, $threshold) {
            $mine = $subs->get($s->id, collect())->keyBy('assignment_id');

            $missing = $due->filter(fn ($a) => AssignmentStatus::resolve(
                $mine->get($a->id)?->status, $a->due_date->format('Y-m-d'), $today, $enrolledOn->get($s->id), $this->recordedOn($a)
            ) === AssignmentStatus::MISSING)->values();

            $count = $missing->count();
            $review = $count >= $threshold;

            return [
                'student_id' => $s->id,
                'student_number' => $s->student_id,
                'full_name' => $s->full_name,
                'missing_count' => $count,
                'late_count' => $mine->where('status', AssignmentStatus::LATE)->count(),
                'missing' => $missing->map(fn ($a) => [
                    'id' => $a->id, 'title' => $a->title, 'due_date' => $a->due_date->format('Y-m-d'),
                ])->all(),
                'needs_review' => $review,
                'label' => $review ? 'Missing work' : null,
                'reasons' => $review
                    ? ["{$count} overdue assignments have no submission recorded (review threshold {$threshold})."]
                    : [],
            ];
        })->sortByDesc('missing_count')->values();

        return [
            'threshold' => $threshold,
            'assignments_due' => $due->count(),
            'students' => $students,
            'summary' => ['needs_review_count' => $students->where('needs_review', true)->count()],
        ];
    }

    // ---------------------------------------------------------------- recording

    /**
     * Record hand-ins for many students at once (also used for corrections).
     * outcome: submitted | excused | clear. For "submitted" the server decides on-time or late from the
     * date (default: today in the school timezone). Rows that did not change are not rewritten.
     * Returns the number of rows changed.
     */
    public function saveSubmissions(Assignment $a, array $records, User $user): int
    {
        $rosterIds = $this->attendance->roster($a->classAssignment)->pluck('id')->all();
        $ids = array_map('intval', array_column($records, 'student_id'));

        $errors = [];
        if (array_diff($ids, $rosterIds)) {
            $errors['records'][] = 'Some students are not enrolled in this class.';
        }
        if (count($ids) !== count(array_unique($ids))) {
            $errors['records'][] = 'Each student can appear only once.';
        }

        $today = $this->today();
        $assigned = $a->assigned_date->format('Y-m-d');
        $due = $a->due_date->format('Y-m-d');

        foreach ($records as $i => $r) {
            if ($r['outcome'] !== 'submitted') continue;
            $date = ($r['submitted_date'] ?? null) ?: $today;
            if ($date > $today) $errors["records.$i.submitted_date"][] = 'The submitted date cannot be in the future.';
            if ($date < $assigned) $errors["records.$i.submitted_date"][] = 'The submitted date cannot be before the date assigned.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $existing = AssignmentSubmission::where('assignment_id', $a->id)->whereIn('student_id', $ids)->get()->keyBy('student_id');
        $changed = 0;

        DB::transaction(function () use ($a, $records, $user, $existing, $today, $due, &$changed) {
            foreach ($records as $r) {
                $studentId = (int) $r['student_id'];
                $row = $existing->get($studentId);

                if ($r['outcome'] === 'clear') {
                    if ($row) {
                        AuditLogger::log($user, $row, 'deleted', $this->submissionValues($row), null);
                        $row->delete();
                        $changed++;
                    }
                    continue;
                }

                $date = $r['outcome'] === 'submitted' ? (($r['submitted_date'] ?? null) ?: $today) : null;
                $status = $r['outcome'] === 'excused' ? AssignmentStatus::EXCUSED : AssignmentStatus::forDate($date, $due);
                $feedback = $r['feedback'] ?? null;

                if ($row && $row->status === $status
                    && $row->submitted_date?->format('Y-m-d') === $date
                    && $row->feedback === $feedback) {
                    continue; // nothing changed
                }

                $old = $row ? $this->submissionValues($row) : null;
                $feedbackChanged = ($row?->feedback) !== $feedback;
                $payload = ['status' => $status, 'submitted_date' => $date, 'feedback' => $feedback, 'recorded_by' => $user->id];

                if ($row) {
                    $row->update($payload);
                } else {
                    $row = AssignmentSubmission::create($payload + ['assignment_id' => $a->id, 'student_id' => $studentId]);
                }

                // The feedback text is never written to the audit log, only whether it changed.
                AuditLogger::log($user, $row, $old ? 'updated' : 'created', $old,
                    $this->submissionValues($row->refresh()) + ['feedback_changed' => $feedbackChanged]);
                $changed++;
            }
        });

        return $changed;
    }

    // ---------------------------------------------------------------- internals

    /** Stored submitted/late records follow the due date: recalculate them after it changes. */
    private function recalculate(Assignment $a): int
    {
        $due = $a->due_date->format('Y-m-d');
        $changed = 0;

        $rows = $a->submissions()
            ->whereIn('status', [AssignmentStatus::SUBMITTED, AssignmentStatus::LATE])
            ->whereNotNull('submitted_date')->get();

        foreach ($rows as $row) {
            $status = AssignmentStatus::forDate($row->submitted_date->format('Y-m-d'), $due);
            if ($status !== $row->status) {
                $row->update(['status' => $status]);
                $changed++;
            }
        }

        return $changed;
    }

    /** @return array{0: Collection, 1: Collection} the active roster and student_id => date they joined (school timezone) */
    private function context(ClassAssignment $ca): array
    {
        $roster = $this->attendance->roster($ca);
        $tz = config('monitoring.timezone');

        $enrolledOn = Enrollment::where('class_assignment_id', $ca->id)
            ->whereIn('student_id', $roster->pluck('id'))
            ->get(['student_id', 'created_at'])
            ->mapWithKeys(fn ($e) => [$e->student_id => $e->created_at?->copy()->timezone($tz)->toDateString()]);

        return [$roster, $enrolledOn];
    }

    /** The date the assignment was entered in the system, in the school timezone. */
    private function recordedOn(Assignment $a): ?string
    {
        return $a->created_at?->copy()->timezone(config('monitoring.timezone'))->toDateString();
    }

    /** student_id => status for one assignment. */
    private function statusMap(Assignment $a, Collection $roster, Collection $enrolledOn, Collection $subs, string $today): Collection
    {
        $due = $a->due_date->format('Y-m-d');

        return $roster->mapWithKeys(fn ($s) => [
            $s->id => AssignmentStatus::resolve($subs->get($s->id)?->status, $due, $today, $enrolledOn->get($s->id), $this->recordedOn($a)),
        ]);
    }

    /**
     * Counts from the status map. Late counts as handed in; excused and not-applicable students
     * are left out of the submission-rate denominator.
     */
    private function statsFrom(Collection $statuses): array
    {
        $c = $statuses->countBy();
        $submitted = $c[AssignmentStatus::SUBMITTED] ?? 0;
        $late = $c[AssignmentStatus::LATE] ?? 0;
        $excused = $c[AssignmentStatus::EXCUSED] ?? 0;
        $notApplicable = $c[AssignmentStatus::NOT_APPLICABLE] ?? 0;
        $total = $statuses->count() - $notApplicable;
        $turnedIn = $submitted + $late;
        $expected = $total - $excused;

        return [
            'total' => $total,
            'submitted' => $submitted,
            'late' => $late,
            'excused' => $excused,
            'missing' => $c[AssignmentStatus::MISSING] ?? 0,
            'not_yet' => $c[AssignmentStatus::NOT_YET] ?? 0,
            'not_applicable' => $notApplicable,
            'turned_in' => $turnedIn,
            'submission_rate' => $expected > 0 ? round($turnedIn / $expected * 100, 1) : null,
        ];
    }

    private function values(Assignment $a): array
    {
        return [
            'class_assignment_id' => $a->class_assignment_id,
            'title' => $a->title,
            'assigned_date' => $a->assigned_date->format('Y-m-d'),
            'due_date' => $a->due_date->format('Y-m-d'),
        ];
    }

    private function submissionValues(AssignmentSubmission $s): array
    {
        return [
            'student_id' => $s->student_id,
            'status' => $s->status,
            'submitted_date' => $s->submitted_date?->format('Y-m-d'),
        ];
    }
}

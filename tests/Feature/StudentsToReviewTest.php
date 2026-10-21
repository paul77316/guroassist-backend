<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\InterventionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * The combined "students to review" list. The prompts themselves are tested with attendance and
 * assignments; this checks that they are combined per student, respect ownership and archiving,
 * show "Intervention active", and stay read-only. Uses the configured default thresholds.
 * "Today" is fixed at 2030-03-10 (school timezone). Refuses to run unless the database is a *_test MySQL database.
 */
class StudentsToReviewTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';
    private const URL = '/api/monitoring/students-to-review';

    private User $owner;
    private User $other;
    private User $admin;
    private ClassAssignment $class;
    private ClassAssignment $classOther;
    private Student $sAttendance;  // attendance prompt only
    private Student $sMissing;     // missing-work prompt only
    private Student $sBoth;        // both prompts
    private Student $sNone;        // no prompt
    private int $seq = 0;

    protected function beforeRefreshingDatabase(): void
    {
        $db = (string) config('database.connections.' . config('database.default') . '.database');
        if (! str_ends_with($db, '_test')) {
            throw new RuntimeException("Refusing to wipe '$db'. Point phpunit.xml at a database whose name ends in _test.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('teacher', 'owner@test.local');
        $this->other = $this->makeUser('teacher', 'other@test.local');
        $this->admin = $this->makeUser('admin', 'admin@test.local');
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create([
            'teacher_id' => $ownerProfile->id, 'subject_id' => $math->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);
        $this->classOther = ClassAssignment::create([
            'teacher_id' => $otherProfile->id, 'subject_id' => $science->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);

        foreach (['sAttendance', 'sMissing', 'sBoth', 'sNone'] as $name) {
            $this->$name = $this->student($this->class);
        }

        // Use the default thresholds from config/monitoring.php, so this follows whatever they are.
        $absences = max(1, (int) config('monitoring.consecutive_absence_threshold'));
        $overdue = max(1, (int) config('monitoring.missing_assignments_threshold'));

        $this->absences($this->sAttendance, $this->class, $absences);
        $this->absences($this->sBoth, $this->class, $absences);

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));

        // Overdue work: sAttendance and sNone handed everything in; sMissing and sBoth handed nothing in.
        foreach ($this->overdueAssignments($this->class, $overdue) as $assignmentId) {
            foreach ([$this->sAttendance, $this->sNone] as $s) {
                AssignmentSubmission::forceCreate([
                    'assignment_id' => $assignmentId, 'student_id' => $s->id, 'status' => 'submitted',
                    'submitted_date' => '2030-03-01', 'recorded_by' => $this->owner->id,
                ]);
            }
        }
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_one_row_per_student_combining_both_prompts(): void
    {
        Sanctum::actingAs($this->owner);

        $res = $this->getJson(self::URL)->assertOk();
        $rows = collect($res->json('data'));

        $this->assertEqualsCanonicalizing(
            [$this->sAttendance->id, $this->sMissing->id, $this->sBoth->id],
            $rows->pluck('student_id')->all()
        );
        $this->assertNull($rows->firstWhere('student_id', $this->sNone->id)); // no prompt, not listed

        $types = fn (Student $s) => collect($rows->firstWhere('student_id', $s->id)['flags'])->pluck('type')->all();
        $this->assertSame(['attendance_concern'], $types($this->sAttendance));
        $this->assertSame(['missing_work'], $types($this->sMissing));
        $this->assertSame(['attendance_concern', 'missing_work'], $types($this->sBoth)); // one row, two prompts

        // The student with the most prompts comes first.
        $this->assertSame($this->sBoth->id, $res->json('data.0.student_id'));

        // Neutral labels, the class and the reasons come with each prompt.
        $both = $rows->firstWhere('student_id', $this->sBoth->id);
        $this->assertSame('Attendance concern', $both['flags'][0]['label']);
        $this->assertSame('Missing work', $both['flags'][1]['label']);
        $this->assertSame($this->class->id, $both['flags'][0]['class']['id']);
        $this->assertSame('Mathematics', $both['flags'][0]['class']['subject']);
        $this->assertNotEmpty($both['flags'][1]['reasons']);
        $this->assertSame($this->class->id, $both['classes'][0]['id']);

        $res->assertJsonPath('meta.today', '2030-03-10')->assertJsonPath('meta.period.from', '2030-02-09');
        $this->assertSame($this->class->id, $res->json('meta.classes.0.id'));
    }

    public function test_an_active_intervention_is_shown_and_a_completed_one_is_not(): void
    {
        $svc = app(InterventionService::class);
        $i = $svc->create($this->owner, $this->sMissing, [
            'concern_type' => 'missing_work', 'concern' => 'SECRET-CONCERN-7731', 'intervention_type' => 'consultation',
            'intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson(self::URL)->assertOk();
        $row = collect($res->json('data'))->firstWhere('student_id', $this->sMissing->id);

        $this->assertSame(1, $row['intervention']['active_count']);
        $this->assertTrue($row['intervention']['follow_up_due']);
        $this->assertStringNotContainsString('SECRET-CONCERN-7731', $res->getContent()); // no free text here

        // Students without any intervention show zero.
        $plain = collect($res->json('data'))->firstWhere('student_id', $this->sAttendance->id);
        $this->assertSame(0, $plain['intervention']['active_count']);
        $this->assertFalse($plain['intervention']['follow_up_due']);

        $svc->addEntry($this->owner, $i, [
            'followup_date' => '2030-03-10', 'to_status' => 'completed', 'outcome' => 'improved',
        ]);
        $row = collect($this->getJson(self::URL)->json('data'))->firstWhere('student_id', $this->sMissing->id);
        $this->assertSame(0, $row['intervention']['active_count']); // still listed: the prompt is unchanged
        $this->assertFalse($row['intervention']['follow_up_due']);
    }

    public function test_it_creates_nothing(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson(self::URL)->assertOk();

        $this->assertSame(0, DB::table('interventions')->count());
        $this->assertSame(0, DB::table('student_notes')->count());
    }

    public function test_each_teacher_sees_only_their_own_classes(): void
    {
        $x = $this->student($this->classOther);
        $this->absences($x, $this->classOther, max(1, (int) config('monitoring.consecutive_absence_threshold')));

        Sanctum::actingAs($this->other);
        $mine = collect($this->getJson(self::URL)->assertOk()->json('data'));
        $this->assertSame([$x->id], $mine->pluck('student_id')->all());

        Sanctum::actingAs($this->owner);
        $this->assertNotContains($x->id, collect($this->getJson(self::URL)->json('data'))->pluck('student_id')->all());
    }

    public function test_admins_get_an_empty_list(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson(self::URL)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_an_archived_class_is_left_out(): void
    {
        $this->class->update(['status' => 'archived']);
        $this->class->delete();

        Sanctum::actingAs($this->owner);
        $this->getJson(self::URL)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_query_count_does_not_grow_with_students(): void
    {
        Sanctum::actingAs($this->owner);

        // The SQL of one request, with "?, ?, ?" lists collapsed so a longer id list is not a difference.
        $queries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson(self::URL)->assertOk();
            $sql = array_map(fn ($q) => preg_replace('/\?(,\s*\?)+/', '?', $q['query']), DB::getQueryLog());
            DB::disableQueryLog();

            return $sql;
        };

        $this->getJson(self::URL)->assertOk(); // warm-up: one-off work should not count
        $few = $queries();

        $absences = max(1, (int) config('monitoring.consecutive_absence_threshold'));
        for ($k = 0; $k < 6; $k++) {
            $s = $this->student($this->class);
            if ($k % 2 === 0) {
                $this->absences($s, $this->class, $absences);
            }
        }

        $this->assertSame($few, $queries());
    }

    public function test_the_active_interventions_endpoint_lists_only_open_ones_by_student(): void
    {
        $svc = app(InterventionService::class);
        $base = [
            'concern_type' => 'missing_work', 'concern' => 'SECRET-CONCERN-7731', 'intervention_type' => 'consultation',
            'intervention_date' => '2030-03-01',
        ];
        $svc->create($this->owner, $this->sMissing, $base + ['follow_up_date' => '2030-03-05']);   // open and due
        $svc->create($this->owner, $this->sMissing, $base + ['follow_up_date' => '2030-03-20']);   // open, not due
        $done = $svc->create($this->owner, $this->sAttendance, $base);                              // completed below
        $svc->addEntry($this->owner, $done, ['followup_date' => '2030-03-10', 'to_status' => 'completed', 'outcome' => 'improved']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/monitoring/active-interventions')->assertOk();

        $res->assertJsonPath("data.{$this->sMissing->id}.active_count", 2)
            ->assertJsonPath("data.{$this->sMissing->id}.follow_up_due", true);
        $this->assertArrayNotHasKey((string) $this->sAttendance->id, $res->json('data')); // completed is not active
        $this->assertArrayNotHasKey((string) $this->sNone->id, $res->json('data'));
        $this->assertStringNotContainsString('SECRET-CONCERN-7731', $res->getContent());   // counts only

        // Another teacher and an admin do not see these.
        Sanctum::actingAs($this->other);
        $this->assertSame([], (array) $this->getJson('/api/monitoring/active-interventions')->assertOk()->json('data'));
        Sanctum::actingAs($this->admin);
        $this->assertSame([], (array) $this->getJson('/api/monitoring/active-interventions')->assertOk()->json('data'));
    }

    public function test_guests_cannot_read_active_interventions(): void
    {
        $this->getJson('/api/monitoring/active-interventions')->assertUnauthorized();
    }

    public function test_an_archived_class_intervention_is_not_active(): void
    {
        $i = app(InterventionService::class)->create($this->owner, $this->sMissing, [
            'concern_type' => 'missing_work', 'concern' => 'x', 'intervention_type' => 'consultation',
            'intervention_date' => '2030-03-01', 'class_assignment_id' => $this->class->id,
        ]);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/monitoring/active-interventions')->assertJsonPath("data.{$this->sMissing->id}.active_count", 1);

        $this->class->update(['status' => 'archived']);
        $this->class->delete();

        $this->assertSame([], (array) $this->getJson('/api/monitoring/active-interventions')->assertOk()->json('data'));
    }
    public function test_a_completed_intervention_marks_that_prompt_as_handled(): void
    {
        Sanctum::actingAs($this->owner);
        $row = fn () => collect($this->getJson(self::URL)->assertOk()->json('data'))->firstWhere('student_id', $this->sBoth->id);

        $before = $row();
        $this->assertTrue($before['needs_attention']);
        $this->assertNull(collect($before['flags'])->keyBy('type')['missing_work']['handled']);

        // Missing work completed: that prompt is handled, the attendance prompt is not.
        $this->completedIntervention($this->sBoth, 'missing_work');
        $mid = $row();
        $flags = collect($mid['flags'])->keyBy('type');
        $this->assertSame('2030-03-10', $flags['missing_work']['handled']['completed_on']);
        $this->assertSame('Improved', $flags['missing_work']['handled']['outcome_label']);
        $this->assertNull($flags['attendance_concern']['handled']);
        $this->assertTrue($mid['needs_attention']);

        // Both completed: nothing left to look at, but the student is still returned (marked) for "Show handled".
        $this->completedIntervention($this->sBoth, 'attendance_concern');
        $this->assertFalse($row()['needs_attention']);
        $this->getJson(self::URL)->assertJsonPath('meta.handled_days', 30);
    }

    public function test_a_different_concern_type_does_not_handle_a_prompt(): void
    {
        $this->completedIntervention($this->sMissing, 'attendance_concern'); // sMissing only has the missing-work prompt

        Sanctum::actingAs($this->owner);
        $row = collect($this->getJson(self::URL)->assertOk()->json('data'))->firstWhere('student_id', $this->sMissing->id);

        $this->assertTrue($row['needs_attention']);
        $this->assertNull($row['flags'][0]['handled']);
    }

    public function test_a_class_intervention_handles_the_prompt_of_its_class(): void
    {
        $this->completedIntervention($this->sMissing, 'missing_work', '2030-03-10', ['class_assignment_id' => $this->class->id]);

        Sanctum::actingAs($this->owner);
        $row = collect($this->getJson(self::URL)->assertOk()->json('data'))->firstWhere('student_id', $this->sMissing->id);

        $this->assertFalse($row['needs_attention']);
    }

    public function test_a_handled_prompt_returns_after_the_window(): void
    {
        Sanctum::actingAs($this->owner);
        $needs = fn () => collect($this->getJson(self::URL)->assertOk()->json('data'))->firstWhere('student_id', $this->sMissing->id)['needs_attention'];

        $this->completedIntervention($this->sMissing, 'missing_work', '2030-02-07'); // 31 days ago: outside the window
        $this->assertTrue($needs());

        $this->completedIntervention($this->sMissing, 'missing_work', '2030-02-08'); // 30 days ago: inside
        $this->assertFalse($needs());
    }

    public function test_a_reopened_intervention_no_longer_handles_the_prompt(): void
    {
        $svc = app(InterventionService::class);
        $i = $svc->create($this->owner, $this->sMissing, [
            'concern_type' => 'missing_work', 'concern' => 'x', 'intervention_type' => 'consultation', 'intervention_date' => '2030-03-01',
        ]);
        $svc->addEntry($this->owner, $i, ['followup_date' => '2030-03-10', 'to_status' => 'completed', 'outcome' => 'improved']);

        Sanctum::actingAs($this->owner);
        $needs = fn () => collect($this->getJson(self::URL)->assertOk()->json('data'))->firstWhere('student_id', $this->sMissing->id)['needs_attention'];
        $this->assertFalse($needs());

        $svc->addEntry($this->owner, $i->refresh(), ['followup_date' => '2030-03-10', 'to_status' => 'in_progress', 'notes' => 'Back again']);
        $this->assertTrue($needs());
    }

    // ---- helper ----

    private function completedIntervention(Student $s, string $type, string $on = '2030-03-10', array $over = []): void
    {
        $svc = app(InterventionService::class);
        $i = $svc->create($this->owner, $s, array_merge([
            'concern_type' => $type, 'concern' => 'x', 'intervention_type' => 'consultation', 'intervention_date' => '2030-02-01',
        ], $over));
        $svc->addEntry($this->owner, $i, ['followup_date' => $on, 'to_status' => 'completed', 'outcome' => 'improved']);
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function student(ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'R-' . (++$this->seq), 'first_name' => 'Student' . str_pad((string) $this->seq, 2, '0', STR_PAD_LEFT),
            'last_name' => 'Test', 'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student->id, 'class_assignment_id' => $class->id,
            'section_id' => $class->section_id, 'academic_year_id' => $class->academic_year_id,
        ]);

        return $student;
    }

    /** $n consecutive absences from 1 March 2030. */
    private function absences(Student $s, ClassAssignment $class, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Attendance::forceCreate([
                'student_id' => $s->id, 'class_assignment_id' => $class->id,
                'attendance_date' => Carbon::parse('2030-03-01')->addDays($i)->toDateString(),
                'status' => 'absent', 'recorded_by' => $this->owner->id,
            ]);
        }
    }

    /** @return int[] ids of $n assignments, all past their due date */
    private function overdueAssignments(ClassAssignment $class, int $n): array
    {
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $ids[] = Assignment::create([
                'class_assignment_id' => $class->id, 'title' => 'Quiz ' . ($i + 1), 'description' => null,
                'assigned_date' => '2030-02-20', 'due_date' => Carbon::parse('2030-03-01')->addDays($i)->toDateString(),
                'created_by' => $this->owner->id,
            ])->id;
        }

        return $ids;
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\MonitoringSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Assignment tracking. "Today" is fixed at 2030-03-10 (school timezone) in every test.
 * RefreshDatabase wipes the database, so this refuses to run unless it is a *_test database.
 */
class AssignmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private User $admin;
    private ClassAssignment $class;
    /** @var Student[] */
    private array $students = [];
    private int $seq = 0;

    protected function beforeRefreshingDatabase(): void
    {
        $db = (string) config('database.connections.' . config('database.default') . '.database');
        if (! str_ends_with($db, '_test') && $db !== ':memory:') {
            throw new \RuntimeException("Refusing to wipe '$db'. Point phpunit.xml at a database whose name ends in _test.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('teacher', 'owner@test.local');
        $this->other = $this->makeUser('teacher', 'other@test.local');
        $this->admin = $this->makeUser('admin', 'admin@test.local');
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create([
            'teacher_id' => $ownerProfile->id, 'subject_id' => $subject->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->students[] = $this->makeStudent('2030-01-02');
        }

        // After the fixtures: "now" is 10 March 2030, mid-morning in Manila.
        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ---------------------------------------------------------------- the rules

    public function test_status_changes_at_midnight_in_the_school_timezone(): void
    {
        $id = $this->createAssignment('2030-03-05');

        // 00:30 on the due date in Manila is still 4 March in UTC
        $this->travelTo(Carbon::parse('2030-03-05 00:30:00', self::TZ));
        $this->assertSame('not_yet', $this->row($this->grid($id), $this->students[0])['status']);

        $this->travelTo(Carbon::parse('2030-03-06 00:30:00', self::TZ));
        $this->assertSame('missing', $this->row($this->grid($id), $this->students[0])['status']);
    }

    public function test_the_server_decides_on_time_or_late_from_the_date(): void
    {
        $id = $this->createAssignment('2030-03-05');

        $this->save($id, [
            $this->record(0, 'submitted', '2030-03-05'), // on the due date
            $this->record(1, 'submitted', '2030-03-06'), // a day after
        ])->assertOk();

        $grid = $this->grid($id);
        $this->assertSame('submitted', $this->row($grid, $this->students[0])['status']);
        $this->assertSame('late', $this->row($grid, $this->students[1])['status']);
    }

    public function test_the_client_cannot_send_late_directly(): void
    {
        $id = $this->createAssignment('2030-03-05');

        $this->save($id, [$this->record(0, 'late', '2030-03-05')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['records.0.outcome']);
    }

    public function test_a_missing_date_defaults_to_today_in_the_school_timezone(): void
    {
        $id = $this->createAssignment('2030-03-05');

        $this->save($id, [$this->record(0, 'submitted')])->assertOk();

        $row = $this->row($this->grid($id), $this->students[0]);
        $this->assertSame('2030-03-10', $row['submitted_date']);
        $this->assertSame('late', $row['status']); // today is after the due date
    }

    public function test_submission_dates_are_validated(): void
    {
        $id = $this->createAssignment('2030-03-05', '2030-03-01');

        $this->save($id, [$this->record(0, 'submitted', '2030-03-11')])
            ->assertUnprocessable()->assertJsonValidationErrors(['records.0.submitted_date']); // future

        $this->save($id, [$this->record(0, 'submitted', '2030-02-28')])
            ->assertUnprocessable()->assertJsonValidationErrors(['records.0.submitted_date']); // before assigned
    }

    public function test_records_can_be_corrected_and_cleared(): void
    {
        $id = $this->createAssignment('2030-03-05');

        $this->save($id, [$this->record(0, 'submitted', '2030-03-06')])->assertOk();
        $this->assertSame('late', $this->row($this->grid($id), $this->students[0])['status']);

        $this->save($id, [$this->record(0, 'submitted', '2030-03-05')])->assertOk(); // correction
        $this->assertSame('submitted', $this->row($this->grid($id), $this->students[0])['status']);

        $this->save($id, [$this->record(0, 'clear')])->assertOk();
        $row = $this->row($this->grid($id), $this->students[0]);
        $this->assertFalse($row['recorded']);
        $this->assertSame('missing', $row['status']);
        $this->assertSame(0, AssignmentSubmission::count());
    }

    public function test_saving_unchanged_rows_writes_nothing(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $records = [$this->record(0, 'submitted', '2030-03-04')];

        $this->save($id, $records)->assertOk()->assertJsonPath('changed', 1);
        $this->save($id, $records)->assertOk()->assertJsonPath('changed', 0);
    }

    public function test_excused_students_are_left_out_of_the_rate_and_the_missing_count(): void
    {
        $id = $this->createAssignment('2030-03-05');

        $this->save($id, [
            $this->record(0, 'excused'),
            $this->record(1, 'submitted', '2030-03-05'),
            $this->record(2, 'submitted', '2030-03-07'), // late
        ])->assertOk();

        $stats = $this->grid($id)['stats'];
        $this->assertSame(4, $stats['total']);
        $this->assertSame(1, $stats['excused']);
        $this->assertSame(1, $stats['late']);
        $this->assertSame(1, $stats['missing']);
        $this->assertSame(2, $stats['turned_in']);                 // late counts as handed in
        $this->assertEquals(66.7, $stats['submission_rate']);      // 2 of (4 - 1 excused)
    }

    // ---------------------------------------------------------------- missing-work monitoring

    public function test_missing_work_flags_at_exactly_the_threshold_and_late_work_is_not_missing(): void
    {
        $a1 = $this->createAssignment('2030-03-01', '2030-02-20');
        $this->createAssignment('2030-03-02', '2030-02-20');
        $this->createAssignment('2030-03-03', '2030-02-20');

        $this->save($a1, [$this->record(1, 'submitted', '2030-03-08')])->assertOk(); // handed in late

        $first = $this->missingFor($this->students[0]);   // nothing handed in
        $second = $this->missingFor($this->students[1]);  // one handed in late

        $this->assertSame(3, $first['missing_count']);
        $this->assertTrue($first['needs_review']);        // default threshold is 3
        $this->assertSame('Missing work', $first['label']);

        $this->assertSame(2, $second['missing_count']);
        $this->assertSame(1, $second['late_count']);
        $this->assertFalse($second['needs_review']);

        MonitoringSetting::create(['teacher_id' => $this->class->teacher_id, 'missing_assignments_threshold' => 2]);
        $this->assertTrue($this->missingFor($this->students[1])['needs_review']);
    }

    public function test_students_added_after_the_assignment_was_due_and_entered_are_not_counted(): void
    {
        $this->travelTo(Carbon::parse('2030-03-04 10:00:00', self::TZ));
        $id = $this->createAssignment('2030-03-05', '2030-03-01'); // entered on 4 March
        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));

        $late = $this->makeStudent('2030-03-08'); // added to the class after it was due and entered

        $this->assertSame('not_applicable', $this->row($this->grid($id), $late)['status']);
        $this->assertSame(4, $this->grid($id)['stats']['total']); // not 5
        $this->assertSame(0, $this->missingFor($late)['missing_count']);
    }

    public function test_a_back_dated_assignment_counts_students_who_were_already_added(): void
    {
        $recent = $this->makeStudent('2030-03-08'); // added to the app recently
        $id = $this->createAssignment('2030-03-05'); // entered today (10 March) with an earlier due date

        $this->assertSame('missing', $this->row($this->grid($id), $recent)['status']);
        $this->assertSame(5, $this->grid($id)['stats']['total']);
        $this->assertSame(1, $this->missingFor($recent)['missing_count']);
    }

    // ---------------------------------------------------------------- date changes

    public function test_changing_the_due_date_recalculates_statuses(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $this->save($id, [$this->record(0, 'submitted', '2030-03-04')])->assertOk();

        Sanctum::actingAs($this->owner);

        // due date moves before the hand-in: now late
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-03'))
            ->assertOk()->assertJsonPath('meta.recalculated', 1);
        $grid = $this->grid($id);
        $this->assertSame('late', $this->row($grid, $this->students[0])['status']);
        $this->assertSame('missing', $this->row($grid, $this->students[1])['status']);

        // and back: on time again
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-05'))
            ->assertOk()->assertJsonPath('meta.recalculated', 1);
        $this->assertSame('submitted', $this->row($this->grid($id), $this->students[0])['status']);

        // due date in the future: derived "missing" flips to "not yet" by itself
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-20'))
            ->assertOk()->assertJsonPath('meta.recalculated', 0);
        $this->assertSame('not_yet', $this->row($this->grid($id), $this->students[1])['status']);
    }

    public function test_the_date_assigned_cannot_move_past_an_existing_submission(): void
    {
        $id = $this->createAssignment('2030-03-05', '2030-03-01');
        $this->save($id, [$this->record(0, 'submitted', '2030-03-02')])->assertOk();

        Sanctum::actingAs($this->owner);
        $this->putJson("/api/assignments/$id", $this->body('2030-03-04', '2030-03-05'))
            ->assertUnprocessable()->assertJsonValidationErrors(['assigned_date']);
    }

    // ---------------------------------------------------------------- soft delete

    public function test_deleted_assignments_stop_counting_and_can_be_restored(): void
    {
        $id = $this->createAssignment('2030-03-01', '2030-02-20');
        $this->createAssignment('2030-03-02', '2030-02-20');
        $this->createAssignment('2030-03-03', '2030-02-20');
        $this->save($id, [$this->record(1, 'submitted', '2030-03-01', 'kept')])->assertOk();
        $this->assertSame(3, $this->missingFor($this->students[0])['missing_count']);

        Sanctum::actingAs($this->owner);
        $this->deleteJson("/api/assignments/$id")->assertNoContent();

        $this->assertSame(2, $this->missingFor($this->students[0])['missing_count']);
        $classId = $this->class->id;
        $this->assertNotContains($id, collect($this->getJson("/api/assignments?class_assignment_id=$classId")->json('data'))->pluck('id')->all());
        $this->assertContains($id, collect($this->getJson("/api/assignments?class_assignment_id=$classId&trashed=only")->json('data'))->pluck('id')->all());
        $this->assertSame(1, AssignmentSubmission::count()); // the history is preserved

        Sanctum::actingAs($this->other);
        $this->postJson("/api/assignments/$id/restore")->assertForbidden();

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/assignments/$id/restore")->assertOk();
        $this->assertSame(3, $this->missingFor($this->students[0])['missing_count']);

        $this->postJson("/api/assignments/$id/restore")->assertUnprocessable(); // not deleted any more
    }

    // ---------------------------------------------------------------- authorization

    public function test_archived_classes_reject_new_assignments_and_teacher_changes(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $this->class->update(['status' => 'archived']);
        $this->class->delete(); // archiving soft-deletes the class

        Sanctum::actingAs($this->owner);
        $this->postJson('/api/assignments', $this->body('2030-03-01', '2030-03-05') + ['class_assignment_id' => $this->class->id])
            ->assertUnprocessable();
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-06'))->assertForbidden();
        $this->putJson("/api/assignments/$id/submissions", ['records' => [$this->record(0, 'submitted', '2030-03-05')]])->assertForbidden();
        $this->deleteJson("/api/assignments/$id")->assertForbidden();
        $this->getJson('/api/assignments?class_assignment_id=' . $this->class->id)->assertOk(); // history stays readable
        $this->getJson("/api/assignments/$id")->assertOk();

        Sanctum::actingAs($this->admin); // admins can still correct records
        $this->putJson("/api/assignments/$id/submissions", ['records' => [$this->record(0, 'submitted', '2030-03-05')]])->assertOk();
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-06'))->assertOk();
    }

    public function test_another_teacher_is_blocked_everywhere_and_the_owner_and_admin_are_not(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $q = 'class_assignment_id=' . $this->class->id;
        $body = $this->body('2030-03-01', '2030-03-05');
        $records = ['records' => [$this->record(0, 'submitted', '2030-03-05')]];

        $calls = [
            ['getJson', "/api/assignments?$q", []],
            ['getJson', "/api/assignments/missing?$q", []],
            ['postJson', '/api/assignments', $body + ['class_assignment_id' => $this->class->id]],
            ['getJson', "/api/assignments/$id", []],
            ['putJson', "/api/assignments/$id", $body],
            ['putJson', "/api/assignments/$id/submissions", $records],
            ['postJson', "/api/assignments/$id/restore", []],
            ['deleteJson', "/api/assignments/$id", []],
        ];

        Sanctum::actingAs($this->other);
        foreach ($calls as [$method, $url, $data]) {
            $this->$method($url, $data)->assertForbidden();
        }
        $this->assertSame(1, Assignment::count()); // nothing was changed or deleted

        foreach ([$this->owner, $this->admin] as $user) {
            Sanctum::actingAs($user);
            $this->getJson("/api/assignments?$q")->assertOk();
            $this->getJson("/api/assignments/missing?$q")->assertOk();
            $this->getJson("/api/assignments/$id")->assertOk();
            $this->putJson("/api/assignments/$id", $body)->assertOk();
            $this->putJson("/api/assignments/$id/submissions", $records)->assertOk();
        }
    }

    public function test_guests_are_rejected(): void
    {
        $a = Assignment::create([
            'class_assignment_id' => $this->class->id, 'title' => 'Quiz',
            'assigned_date' => '2030-03-01', 'due_date' => '2030-03-05',
        ]);

        $this->getJson('/api/assignments?class_assignment_id=' . $this->class->id)->assertUnauthorized();
        $this->getJson("/api/assignments/{$a->id}")->assertUnauthorized();
        $this->putJson("/api/assignments/{$a->id}/submissions", ['records' => []])->assertUnauthorized();
        $this->deleteJson("/api/assignments/{$a->id}")->assertUnauthorized();
    }

    public function test_students_outside_the_class_are_rejected(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $outsider = Student::create([
            'student_id' => 'OUT-1', 'first_name' => 'Out', 'last_name' => 'Sider',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);

        $this->save($id, [['student_id' => $outsider->id, 'outcome' => 'submitted', 'submitted_date' => '2030-03-05']])
            ->assertUnprocessable()->assertJsonValidationErrors(['records']);
        $this->assertSame(0, AssignmentSubmission::count());
    }

    // ---------------------------------------------------------------- privacy and audit

    public function test_private_feedback_appears_only_in_the_grid_and_never_in_the_audit_log(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $this->save($id, [$this->record(0, 'submitted', '2030-03-04', 'SECRET-NOTE')])->assertOk();

        $this->assertSame('SECRET-NOTE', $this->row($this->grid($id), $this->students[0])['feedback']);

        Sanctum::actingAs($this->owner);
        $q = 'class_assignment_id=' . $this->class->id;
        foreach (["/api/assignments?$q", "/api/assignments/missing?$q"] as $url) {
            $body = $this->getJson($url)->getContent();
            $this->assertStringNotContainsString('SECRET-NOTE', $body);
            $this->assertStringNotContainsString('feedback', $body);
        }

        $this->assertStringNotContainsString('SECRET-NOTE', AuditLog::all()->toJson());
        $this->assertStringNotContainsString('SECRET-NOTE', json_encode(AssignmentSubmission::first()->toArray()));
    }

    public function test_changes_are_audited(): void
    {
        $id = $this->createAssignment('2030-03-05');
        $this->save($id, [$this->record(0, 'submitted', '2030-03-04')])->assertOk();
        $this->save($id, [$this->record(0, 'clear')])->assertOk();

        Sanctum::actingAs($this->owner);
        $this->putJson("/api/assignments/$id", $this->body('2030-03-01', '2030-03-06'))->assertOk();
        $this->deleteJson("/api/assignments/$id")->assertNoContent();
        $this->postJson("/api/assignments/$id/restore")->assertOk();

        $assignmentEvents = AuditLog::where('auditable_type', Assignment::class)->pluck('event')->all();
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            $this->assertContains($event, $assignmentEvents);
        }

        $submissionEvents = AuditLog::where('auditable_type', AssignmentSubmission::class)->pluck('event')->all();
        $this->assertContains('created', $submissionEvents);
        $this->assertContains('deleted', $submissionEvents);
    }

    // ---------------------------------------------------------------- efficiency

    public function test_the_number_of_queries_does_not_grow_with_students_or_assignments(): void
    {
        $this->createAssignment('2030-03-01', '2030-02-20');

        $measure = function (string $url) {
            Sanctum::actingAs($this->owner);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson($url)->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };
        $list = '/api/assignments?class_assignment_id=' . $this->class->id;
        $missing = '/api/assignments/missing?class_assignment_id=' . $this->class->id;

        $listBefore = $measure($list);
        $missingBefore = $measure($missing);

        for ($i = 0; $i < 20; $i++) {
            $this->makeStudent('2030-01-02');
        }
        for ($i = 2; $i <= 4; $i++) {
            $this->createAssignment("2030-03-0$i", '2030-02-20');
        }

        $this->assertSame($listBefore, $measure($list));
        $this->assertSame($missingBefore, $measure($missing));
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function makeStudent(string $enrolledOn): Student
    {
        $student = Student::create([
            'student_id' => 'T-' . (++$this->seq), 'first_name' => 'Student' . $this->seq, 'last_name' => 'Test',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id, 'class_assignment_id' => $this->class->id,
            'section_id' => $this->class->section_id, 'academic_year_id' => $this->class->academic_year_id,
        ]);
        $enrollment->forceFill(['created_at' => Carbon::parse($enrolledOn, self::TZ)->utc()])->saveQuietly();

        return $student;
    }

    private function body(string $assigned, string $due): array
    {
        return ['title' => 'Quiz', 'description' => null, 'assigned_date' => $assigned, 'due_date' => $due];
    }

    private function createAssignment(string $due, string $assigned = '2030-03-01'): int
    {
        Sanctum::actingAs($this->owner);

        return $this->postJson('/api/assignments', $this->body($assigned, $due) + ['class_assignment_id' => $this->class->id])
            ->assertCreated()->json('data.id');
    }

    /** One entry for the bulk save, for the n-th test student. */
    private function record(int $student, string $outcome, ?string $date = null, ?string $feedback = null): array
    {
        return [
            'student_id' => $this->students[$student]->id,
            'outcome' => $outcome,
            'submitted_date' => $date,
            'feedback' => $feedback,
        ];
    }

    private function save(int $id, array $records)
    {
        Sanctum::actingAs($this->owner);

        return $this->putJson("/api/assignments/$id/submissions", ['records' => $records]);
    }

    private function grid(int $id): array
    {
        Sanctum::actingAs($this->owner);

        return $this->getJson("/api/assignments/$id")->assertOk()->json('data');
    }

    private function row(array $grid, Student $student): array
    {
        return collect($grid['students'])->firstWhere('student_id', $student->id);
    }

    private function missingFor(Student $student): array
    {
        Sanctum::actingAs($this->owner);
        $rows = $this->getJson('/api/assignments/missing?class_assignment_id=' . $this->class->id)->assertOk()->json('data.students');

        return collect($rows)->firstWhere('student_id', $student->id);
    }
}

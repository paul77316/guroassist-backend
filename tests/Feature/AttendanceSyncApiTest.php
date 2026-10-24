<?php

namespace Tests\Feature;

use App\Jobs\SendGuardianAttendanceNotice;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSyncOperation;
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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Offline attendance sync endpoint. "Today" is fixed at 2030-03-10 (school timezone).
 * Needs a *_test MySQL database, like the other feature tests.
 */
class AttendanceSyncApiTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2030-03-10';

    private User $owner;

    private User $other;

    private User $admin;

    private Teacher $ownerProfile;

    private Teacher $otherProfile;

    private ClassAssignment $class;

    private Student $student;

    private Student $student2;

    private int $seq = 0;

    protected function beforeRefreshingDatabase(): void
    {
        $db = (string) config('database.connections.'.config('database.default').'.database');
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
        $this->ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        $this->otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create([
            'teacher_id' => $this->ownerProfile->id, 'subject_id' => $math->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);
        $this->student = $this->makeStudent($this->class);
        $this->student2 = $this->makeStudent($this->class);

        $this->travelTo(Carbon::parse(self::DATE.' 10:00:00', 'Asia/Manila'));
    }

    // ---------------------------------------------------------------- access

    public function test_guests_are_rejected_and_nothing_is_stored(): void
    {
        $this->postJson('/api/attendance/sync', ['operations' => [$this->op()]])->assertUnauthorized();

        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, AttendanceSyncOperation::count());
    }

    public function test_a_malformed_batch_is_rejected_whole(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/attendance/sync', ['operations' => []])->assertUnprocessable();
        $this->postJson('/api/attendance/sync', ['operations' => [$this->op(['status' => 'sleeping'])]])->assertUnprocessable();
        $this->postJson('/api/attendance/sync', ['operations' => [$this->op(['operation_id' => 'nope'])]])->assertUnprocessable();

        $id = (string) Str::uuid();
        $this->postJson('/api/attendance/sync', ['operations' => [$this->op(['operation_id' => $id]), $this->op(['operation_id' => $id])]])
            ->assertUnprocessable();
    }

    public function test_another_teachers_class_is_rejected_per_operation(): void
    {
        Sanctum::actingAs($this->other);

        $this->sync([$this->op()])->assertOk()
            ->assertJsonPath('data.0.outcome', 'rejected')
            ->assertJsonPath('data.0.reason', 'forbidden');

        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, AttendanceSyncOperation::count());
    }

    public function test_losing_access_to_the_class_before_sync_is_rejected(): void
    {
        $this->class->update(['teacher_id' => $this->otherProfile->id]);
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op()])->assertJsonPath('data.0.reason', 'forbidden');
        $this->assertSame(0, Attendance::count());
    }

    public function test_admins_can_sync_for_any_class(): void
    {
        Sanctum::actingAs($this->admin);

        $this->sync([$this->op()])->assertJsonPath('data.0.outcome', 'applied');
    }

    public function test_an_archived_or_deleted_class_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->class->update(['status' => 'archived']);
        $this->sync([$this->op()])->assertJsonPath('data.0.reason', 'class_archived');

        $this->class->update(['status' => 'active']);
        $this->class->delete();
        $this->sync([$this->op()])->assertJsonPath('data.0.reason', 'class_archived');

        $this->assertSame(0, Attendance::count());
    }

    public function test_a_student_no_longer_enrolled_is_rejected(): void
    {
        Enrollment::where('student_id', $this->student->id)->delete();
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op()])->assertJsonPath('data.0.reason', 'not_enrolled');
        $this->assertSame(0, Attendance::count());
    }

    public function test_a_future_date_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op(['date' => '2030-03-11'])])->assertJsonPath('data.0.reason', 'future_date');
        $this->assertSame(0, Attendance::count());
    }

    // ---------------------------------------------------------------- applying

    public function test_a_new_operation_is_committed_acknowledged_and_audited(): void
    {
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'late', 'remarks' => 'Bus delay']);

        $this->sync([$op])->assertOk()
            ->assertJsonPath('data.0.operation_id', $op['operation_id'])
            ->assertJsonPath('data.0.outcome', 'applied')
            ->assertJsonPath('data.0.replayed', false)
            ->assertJsonPath('data.0.version', 1);

        $row = Attendance::firstOrFail();
        $this->assertSame('late', $row->status);
        $this->assertSame('Bus delay', $row->remarks);
        $this->assertSame($this->owner->id, $row->recorded_by);
        $this->assertSame(1, $row->sync_version);
        $this->assertSame('applied', AttendanceSyncOperation::firstOrFail()->outcome);
        $this->assertSame(1, AuditLog::where('event', 'attendance.sync_applied')->count());
    }

    public function test_retry_after_a_timeout_replays_the_receipt_without_duplicating(): void
    {
        Sanctum::actingAs($this->owner);
        $op = $this->op();

        $this->sync([$op])->assertJsonPath('data.0.outcome', 'applied');
        // The device never saw that answer, so it sends the very same operation again.
        $this->sync([$op])->assertOk()
            ->assertJsonPath('data.0.outcome', 'applied')
            ->assertJsonPath('data.0.replayed', true)
            ->assertJsonPath('data.0.version', 1);

        $this->assertSame(1, Attendance::count());
        $this->assertSame(1, AttendanceSyncOperation::count());
        $this->assertSame(1, Attendance::firstOrFail()->sync_version);
        $this->assertSame(1, AuditLog::where('event', 'attendance.sync_applied')->count());
    }

    public function test_the_same_operation_id_with_a_different_payload_is_rejected_and_changes_nothing(): void
    {
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent']);
        $this->sync([$op]);

        $this->sync([array_merge($op, ['status' => 'present'])])
            ->assertJsonPath('data.0.outcome', 'rejected')
            ->assertJsonPath('data.0.reason', 'operation_id_reused');

        $this->assertSame('absent', Attendance::firstOrFail()->status);
    }

    public function test_an_operation_id_cannot_be_replayed_by_another_user(): void
    {
        Sanctum::actingAs($this->owner);
        $op = $this->op();
        $this->sync([$op]);

        Sanctum::actingAs($this->admin);
        $this->sync([$op])->assertJsonPath('data.0.reason', 'operation_id_reused');
    }

    public function test_a_partial_batch_returns_one_outcome_per_operation(): void
    {
        $stranger = $this->makeStudent(null);
        Sanctum::actingAs($this->owner);

        $good = $this->op();
        $bad = $this->op(['student_id' => $stranger->id]);
        $good2 = $this->op(['student_id' => $this->student2->id, 'status' => 'present']);

        $this->sync([$good, $bad, $good2])->assertOk()
            ->assertJsonPath('data.0.outcome', 'applied')
            ->assertJsonPath('data.1.outcome', 'rejected')
            ->assertJsonPath('data.1.reason', 'not_enrolled')
            ->assertJsonPath('data.2.outcome', 'applied');

        $this->assertSame(2, Attendance::count());
        $this->assertSame(2, AttendanceSyncOperation::count());
    }

    public function test_two_edits_of_one_student_in_one_batch_apply_in_order(): void
    {
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op(['status' => 'absent']), $this->op(['status' => 'late'])])
            ->assertJsonPath('data.0.outcome', 'applied')
            ->assertJsonPath('data.1.outcome', 'applied')
            ->assertJsonPath('data.1.version', 2);

        $this->assertSame('late', Attendance::firstOrFail()->status);
        $this->assertSame(1, Attendance::count());
    }

    // ---------------------------------------------------------------- conflicts

    public function test_a_stale_version_is_a_conflict_and_never_overwrites_the_server(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);

        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op])->assertOk()
            ->assertJsonPath('data.0.outcome', 'conflict')
            ->assertJsonPath('data.0.server.status', 'excused')
            ->assertJsonPath('data.0.server.version', 3);

        $row = Attendance::firstOrFail();
        $this->assertSame('excused', $row->status);
        $this->assertSame(3, $row->sync_version);
        $this->assertSame('conflict', AttendanceSyncOperation::firstOrFail()->outcome);

        // Retrying the same operation stays a conflict.
        $this->sync([$op])->assertJsonPath('data.0.outcome', 'conflict')->assertJsonPath('data.0.replayed', true);
        $this->getJson('/api/attendance/sync/readiness')
            ->assertJsonPath('data.unresolved_conflicts', 1)
            ->assertJsonPath('data.server_ready', false);
    }

    public function test_a_record_deleted_on_the_server_is_a_conflict(): void
    {
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op(['base_version' => 2])])
            ->assertJsonPath('data.0.outcome', 'conflict')
            ->assertJsonPath('data.0.server.status', null);

        $this->assertSame(0, Attendance::count());
    }

    public function test_the_same_mark_already_on_the_server_converges_without_a_write(): void
    {
        $this->serverMark('absent', 4);
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op(['status' => 'absent', 'base_version' => 1])])
            ->assertJsonPath('data.0.outcome', 'applied')
            ->assertJsonPath('data.0.version', 4);

        $this->assertSame(4, Attendance::firstOrFail()->sync_version);
        $this->assertSame(0, AuditLog::where('event', 'attendance.sync_applied')->count());
    }

    public function test_a_web_bulk_save_moves_the_version_so_an_older_device_conflicts(): void
    {
        Sanctum::actingAs($this->owner);
        $this->sync([$this->op(['status' => 'absent'])])->assertJsonPath('data.0.version', 1);

        $this->postJson('/api/attendance/bulk', [
            'class_assignment_id' => $this->class->id, 'date' => self::DATE,
            'records' => [['student_id' => $this->student->id, 'status' => 'excused']],
        ])->assertOk();

        $this->assertSame(2, Attendance::firstOrFail()->sync_version);
        $this->sync([$this->op(['status' => 'late', 'base_version' => 1])])->assertJsonPath('data.0.outcome', 'conflict');
        $this->assertSame('excused', Attendance::firstOrFail()->status);
    }

    public function test_keep_local_applies_the_device_mark_and_clears_the_gate(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op]);

        $this->postJson("/api/attendance/sync/{$op['operation_id']}/resolve", ['resolution' => 'keep_local'])
            ->assertOk()
            ->assertJsonPath('data.outcome', 'applied')
            ->assertJsonPath('data.version', 4);

        $row = Attendance::firstOrFail();
        $this->assertSame('absent', $row->status);
        $this->assertSame(4, $row->sync_version);
        $this->assertSame(1, AuditLog::where('event', 'attendance.conflict_keep_local')->count());
        $this->getJson('/api/attendance/sync/readiness')->assertJsonPath('data.server_ready', true);
    }

    public function test_use_server_keeps_the_server_mark_and_discards_the_device_mark(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op]);

        $this->postJson("/api/attendance/sync/{$op['operation_id']}/resolve", ['resolution' => 'use_server'])
            ->assertOk()
            ->assertJsonPath('data.outcome', 'discarded')
            ->assertJsonPath('data.server.status', 'excused');

        $this->assertSame('excused', Attendance::firstOrFail()->status);
        $this->assertSame(1, AuditLog::where('event', 'attendance.conflict_use_server')->count());
        $this->getJson('/api/attendance/sync/readiness')->assertJsonPath('data.server_ready', true);
    }

    public function test_resolving_twice_is_safe_but_cannot_flip_the_decision(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op]);
        $url = "/api/attendance/sync/{$op['operation_id']}/resolve";

        $this->postJson($url, ['resolution' => 'keep_local'])->assertOk();
        $this->postJson($url, ['resolution' => 'keep_local'])->assertOk()->assertJsonPath('data.version', 4);
        $this->postJson($url, ['resolution' => 'use_server'])->assertUnprocessable();

        $this->assertSame(4, Attendance::firstOrFail()->sync_version);
    }

    public function test_only_someone_with_class_access_can_resolve_a_conflict(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op]);
        $url = "/api/attendance/sync/{$op['operation_id']}/resolve";

        Sanctum::actingAs($this->other);
        $this->postJson($url, ['resolution' => 'keep_local'])->assertForbidden();
        $this->assertSame('excused', Attendance::firstOrFail()->status);

        Sanctum::actingAs($this->admin);
        $this->postJson($url, ['resolution' => 'keep_local'])->assertOk();
    }

    public function test_a_conflict_cannot_be_resolved_after_the_class_is_archived(): void
    {
        $this->serverMark('excused', 3);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent', 'base_version' => 1]);
        $this->sync([$op]);
        $this->class->update(['status' => 'archived']);

        $this->postJson("/api/attendance/sync/{$op['operation_id']}/resolve", ['resolution' => 'keep_local'])->assertUnprocessable();
        $this->assertSame('excused', Attendance::firstOrFail()->status);
    }

    // ---------------------------------------------------------------- integration

    public function test_confirmed_absence_queues_one_guardian_notice_even_when_retried(): void
    {
        Queue::fake();
        $this->student->update(['guardian_email' => 'parent@test.local']);
        MonitoringSetting::create(['teacher_id' => $this->ownerProfile->id, 'guardian_email_enabled' => true, 'notify_on_absent' => true]);
        Sanctum::actingAs($this->owner);
        $op = $this->op(['status' => 'absent']);

        $this->sync([$op]);
        $this->sync([$op]);

        Queue::assertPushed(SendGuardianAttendanceNotice::class, 1);
    }

    public function test_rejected_and_conflicting_operations_queue_no_guardian_notice(): void
    {
        Queue::fake();
        $this->student->update(['guardian_email' => 'parent@test.local']);
        MonitoringSetting::create(['teacher_id' => $this->ownerProfile->id, 'guardian_email_enabled' => true, 'notify_on_absent' => true]);
        $this->serverMark('present', 2);
        Sanctum::actingAs($this->owner);

        $this->sync([$this->op(['status' => 'absent', 'base_version' => 1])]);

        Queue::assertNothingPushed();
    }

    public function test_attendance_roster_exposes_the_version_devices_must_send_back(): void
    {
        $this->serverMark('late', 5);
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/attendance?class_assignment_id='.$this->class->id.'&date='.self::DATE)
            ->assertOk()
            ->assertJsonFragment(['student_id' => $this->student->id, 'status' => 'late', 'version' => 5])
            ->assertJsonFragment(['student_id' => $this->student2->id, 'status' => null, 'version' => 0]);
    }

    public function test_a_fresh_user_with_no_conflicts_is_server_ready(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/attendance/sync/readiness')
            ->assertOk()
            ->assertJsonPath('data.unresolved_conflicts', 0)
            ->assertJsonPath('data.server_ready', true);
    }

    // ---------------------------------------------------------------- helpers

    private function sync(array $operations)
    {
        return $this->postJson('/api/attendance/sync', ['operations' => $operations]);
    }

    /** @return array<string, mixed> */
    private function op(array $over = []): array
    {
        return array_merge([
            'operation_id' => (string) Str::uuid(),
            'class_assignment_id' => $this->class->id,
            'student_id' => $this->student->id,
            'date' => self::DATE,
            'status' => 'absent',
            'remarks' => null,
            'base_version' => 0,
        ], $over);
    }

    private function serverMark(string $status, int $version): Attendance
    {
        return Attendance::create([
            'student_id' => $this->student->id, 'class_assignment_id' => $this->class->id,
            'attendance_date' => self::DATE, 'status' => $status, 'sync_version' => $version,
            'recorded_by' => $this->admin->id,
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function makeStudent(?ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'N-'.(++$this->seq), 'first_name' => 'Student'.$this->seq, 'last_name' => 'Test',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);

        if ($class) {
            Enrollment::create([
                'student_id' => $student->id, 'class_assignment_id' => $class->id,
                'section_id' => $class->section_id, 'academic_year_id' => $class->academic_year_id,
            ]);
        }

        return $student;
    }
}

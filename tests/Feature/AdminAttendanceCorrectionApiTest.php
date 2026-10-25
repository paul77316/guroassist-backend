<?php

namespace Tests\Feature;

use App\Jobs\SendGuardianAttendanceNotice;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\MobileDevice;
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

/** Admin corrections of attendance marks. "Today" is fixed at 2030-03-10 (school timezone). */
class AdminAttendanceCorrectionApiTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2030-03-10';

    private User $teacher;

    private User $admin;

    private Teacher $profile;

    private ClassAssignment $class;

    private Student $student;

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

        $this->teacher = User::create(['name' => 'Teacher', 'email' => 'teacher@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin']);
        $this->profile = Teacher::create(['user_id' => $this->teacher->id, 'employee_id' => 'T-1']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);
        $this->class = ClassAssignment::create(['teacher_id' => $this->profile->id, 'subject_id' => $math->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active']);

        $this->student = Student::create(['student_id' => 'N-1', 'first_name' => 'Ana', 'last_name' => 'Test', 'sex' => 'female', 'date_of_birth' => '2015-01-01', 'status' => 'active']);
        Enrollment::create(['student_id' => $this->student->id, 'class_assignment_id' => $this->class->id, 'section_id' => $section->id, 'academic_year_id' => $year->id]);

        $this->travelTo(Carbon::parse(self::DATE.' 10:00:00', 'Asia/Manila'));
    }

    public function test_only_admins_can_correct_a_mark(): void
    {
        $this->postJson('/api/admin/attendance/corrections', $this->body())->assertUnauthorized();

        Sanctum::actingAs($this->teacher);
        $this->postJson('/api/admin/attendance/corrections', $this->body())->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_an_admin_can_correct_an_existing_mark_and_the_version_moves(): void
    {
        $this->mark('absent', 1);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'excused', 'version' => 1, 'reason' => 'Parent note']))
            ->assertOk()
            ->assertJsonPath('data.status', 'excused')
            ->assertJsonPath('data.version', 2);

        $row = Attendance::firstOrFail();
        $this->assertSame('excused', $row->status);
        $this->assertSame($this->admin->id, $row->recorded_by);

        $log = AuditLog::where('event', 'attendance.admin_corrected')->firstOrFail();
        $this->assertSame('absent', $log->old_values['status']);
        $this->assertSame('excused', $log->new_values['status']);
        $this->assertSame('Parent note', $log->new_values['reason']);
    }

    public function test_an_admin_can_add_a_missing_mark(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'present']))
            ->assertOk()->assertJsonPath('data.version', 1);

        $this->assertSame(1, Attendance::count());
    }

    public function test_a_stale_version_is_refused_and_nothing_changes(): void
    {
        $this->mark('late', 3);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'absent', 'version' => 1]))
            ->assertStatus(409)
            ->assertJsonPath('code', 'stale_version')
            ->assertJsonPath('current.status', 'late')
            ->assertJsonPath('current.version', 3);

        $this->assertSame('late', Attendance::firstOrFail()->status);
        $this->assertSame(0, AuditLog::where('event', 'attendance.admin_corrected')->count());
    }

    public function test_a_reason_is_required_and_the_input_is_validated(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['reason' => '']))->assertUnprocessable();
        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'sleeping']))->assertUnprocessable();
        $this->postJson('/api/admin/attendance/corrections', $this->body(['date' => '10/03/2030']))->assertUnprocessable();

        $this->assertSame(0, Attendance::count());
    }

    public function test_archived_classes_other_students_and_future_dates_are_refused(): void
    {
        $stranger = Student::create(['student_id' => 'N-2', 'first_name' => 'Not', 'last_name' => 'Enrolled', 'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active']);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['student_id' => $stranger->id]))->assertUnprocessable();
        $this->postJson('/api/admin/attendance/corrections', $this->body(['date' => '2030-03-11']))->assertUnprocessable();

        $this->class->update(['status' => 'archived']);
        $this->postJson('/api/admin/attendance/corrections', $this->body())->assertUnprocessable();

        $this->assertSame(0, Attendance::count());
    }

    public function test_a_phone_holding_an_older_version_conflicts_after_a_correction(): void
    {
        $this->mark('absent', 1);
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'excused', 'version' => 1]))->assertOk();

        $device = MobileDevice::create(['user_id' => $this->teacher->id, 'device_uuid' => (string) Str::uuid(), 'name' => 'Phone', 'status' => 'approved']);
        $token = $this->teacher->createToken($device->tokenName(), [MobileDevice::ABILITY])->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->withToken($token)->postJson('/api/mobile/attendance/sync', ['operations' => [[
            'operation_id' => (string) Str::uuid(), 'class_assignment_id' => $this->class->id,
            'student_id' => $this->student->id, 'date' => self::DATE, 'status' => 'late',
            'remarks' => null, 'base_version' => 1,
        ]]])->assertJsonPath('data.0.outcome', 'conflict')->assertJsonPath('data.0.server.status', 'excused');

        $this->assertSame('excused', Attendance::firstOrFail()->status);
    }

    public function test_a_correction_queues_one_guardian_notice_and_only_when_the_status_changes(): void
    {
        Queue::fake();
        $this->student->update(['guardian_email' => 'parent@test.local']);
        MonitoringSetting::create(['teacher_id' => $this->profile->id, 'guardian_email_enabled' => true, 'notify_on_absent' => true]);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'absent']))->assertOk();
        $this->postJson('/api/admin/attendance/corrections', $this->body(['status' => 'absent', 'version' => 1, 'reason' => 'Retyped']))->assertOk();

        Queue::assertPushed(SendGuardianAttendanceNotice::class, 1);
    }

    /** @return array<string, mixed> */
    private function body(array $over = []): array
    {
        return array_merge([
            'class_assignment_id' => $this->class->id, 'student_id' => $this->student->id, 'date' => self::DATE,
            'status' => 'present', 'remarks' => null, 'reason' => 'Admin check', 'version' => 0,
        ], $over);
    }

    private function mark(string $status, int $version): Attendance
    {
        return Attendance::create([
            'student_id' => $this->student->id, 'class_assignment_id' => $this->class->id,
            'attendance_date' => self::DATE, 'status' => $status, 'sync_version' => $version,
            'recorded_by' => $this->teacher->id,
        ]);
    }
}

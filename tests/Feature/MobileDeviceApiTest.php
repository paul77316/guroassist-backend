<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\MobileDevice;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/** Device-token login, admin approval and the web/mobile split. Needs a *_test MySQL database. */
class MobileDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $other;

    private User $admin;

    private ClassAssignment $class;

    private ClassAssignment $classOther;

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
        $this->other = User::create(['name' => 'Other', 'email' => 'other@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin']);
        $profile = Teacher::create(['user_id' => $this->teacher->id, 'employee_id' => 'T-1']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-2']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create(['teacher_id' => $profile->id, 'subject_id' => $math->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active']);
        $this->classOther = ClassAssignment::create(['teacher_id' => $otherProfile->id, 'subject_id' => $math->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active']);

        $student = Student::create(['student_id' => 'N-1', 'first_name' => 'Ana', 'last_name' => 'Test', 'sex' => 'female', 'date_of_birth' => '2015-01-01', 'status' => 'active']);
        Enrollment::create(['student_id' => $student->id, 'class_assignment_id' => $this->class->id, 'section_id' => $section->id, 'academic_year_id' => $year->id]);
    }

    // ---------------------------------------------------------------- login

    public function test_wrong_credentials_and_bad_input_are_refused(): void
    {
        $this->login($this->teacher, password: 'wrong')->assertUnprocessable();
        $this->postJson('/api/mobile/login', ['email' => 'nobody@test.local', 'password' => 'password', 'device_uuid' => (string) Str::uuid(), 'device_name' => 'Phone'])->assertUnprocessable();
        $this->postJson('/api/mobile/login', ['email' => 'teacher@test.local', 'password' => 'password', 'device_uuid' => 'nope', 'device_name' => 'Phone'])->assertUnprocessable();

        $this->assertSame(0, MobileDevice::count());
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_only_teacher_accounts_may_use_the_mobile_app(): void
    {
        $this->login($this->admin)->assertForbidden()->assertJsonPath('code', 'mobile_not_available');

        $noProfile = User::create(['name' => 'No Profile', 'email' => 'np@test.local', 'password' => 'password', 'role' => 'teacher']);
        $this->login($noProfile)->assertForbidden()->assertJsonPath('code', 'mobile_not_available');

        $this->assertSame(0, MobileDevice::count());
    }

    public function test_a_teacher_who_must_change_the_initial_password_is_refused(): void
    {
        $this->teacher->forceFill(['must_change_password' => true, 'initial_password_expires_at' => now()->addDay()])->save();

        $this->login($this->teacher)->assertForbidden()->assertJsonPath('code', 'password_change_required');
        $this->assertSame(0, MobileDevice::count());
    }

    public function test_a_new_device_is_registered_as_pending_and_gets_no_token(): void
    {
        $uuid = (string) Str::uuid();

        $this->login($this->teacher, $uuid)->assertStatus(202)->assertJsonPath('code', 'device_pending')->assertJsonMissingPath('token');
        $this->login($this->teacher, $uuid)->assertStatus(202);

        $this->assertSame(1, MobileDevice::count());
        $this->assertSame('pending', MobileDevice::firstOrFail()->status);
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_an_approved_device_gets_a_working_token_and_a_new_login_replaces_it(): void
    {
        $device = $this->device($this->teacher, 'approved');

        $first = $this->login($this->teacher, $device->device_uuid)->assertOk()->json('token');
        $second = $this->login($this->teacher, $device->device_uuid)->assertOk()->json('token');

        $this->assertSame(1, PersonalAccessToken::count());
        $this->asDevice($first)->getJson('/api/mobile/attendance/sync/readiness')->assertUnauthorized();
        $this->asDevice($second)->getJson('/api/mobile/attendance/sync/readiness')->assertOk()->assertJsonPath('data.server_ready', true);
    }

    // ---------------------------------------------------------------- web / mobile split

    public function test_a_mobile_token_cannot_reach_any_web_route(): void
    {
        $token = $this->tokenFor($this->device($this->teacher, 'approved'));

        $this->asDevice($token)->getJson('/api/students')->assertForbidden()->assertJsonPath('code', 'web_only');
        $this->asDevice($token)->getJson('/api/auth/me')->assertForbidden()->assertJsonPath('code', 'web_only');
        $this->asDevice($token)->putJson("/api/class-assignments/{$this->class->id}/seating-layout", [])->assertForbidden();
    }

    public function test_a_web_session_cannot_reach_the_mobile_routes(): void
    {
        $this->asAdmin();
        $this->getJson('/api/mobile/classes')->assertForbidden()->assertJsonPath('code', 'mobile_token_required');

        Sanctum::actingAs($this->teacher);
        $this->getJson('/api/mobile/attendance/sync/readiness')->assertForbidden();
    }

    public function test_the_mobile_app_can_read_its_own_classes_roster_and_seating(): void
    {
        $token = $this->tokenFor($this->device($this->teacher, 'approved'));

        $this->asDevice($token)->getJson('/api/mobile/classes')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->class->id);
        $this->asDevice($token)->getJson("/api/mobile/attendance?class_assignment_id={$this->class->id}&date=2030-03-10")
            ->assertOk()->assertJsonCount(1, 'data.students');
        $this->asDevice($token)->getJson("/api/mobile/class-assignments/{$this->class->id}/seating-layout")->assertOk();
        $this->asDevice($token)->getJson("/api/mobile/class-assignments/{$this->classOther->id}/seating-layout")->assertForbidden();
    }

    // ---------------------------------------------------------------- token lifecycle

    public function test_logout_and_expiry_end_the_token(): void
    {
        $device = $this->device($this->teacher, 'approved');
        $token = $this->tokenFor($device);

        $this->asDevice($token)->postJson('/api/mobile/logout')->assertNoContent();
        $this->asDevice($token)->getJson('/api/mobile/attendance/sync/readiness')->assertUnauthorized();

        $expired = $this->teacher->createToken($device->tokenName(), [MobileDevice::ABILITY], now()->subMinute())->plainTextToken;
        $this->asDevice($expired)->getJson('/api/mobile/attendance/sync/readiness')->assertUnauthorized();
    }

    public function test_a_token_left_over_from_a_revoked_device_is_refused(): void
    {
        $device = $this->device($this->teacher, 'approved');
        $token = $this->tokenFor($device);
        $device->update(['status' => 'revoked']);

        $this->asDevice($token)->getJson('/api/mobile/attendance/sync/readiness')
            ->assertForbidden()->assertJsonPath('code', 'device_not_approved');
    }

    public function test_a_token_with_a_foreign_name_is_refused(): void
    {
        $token = $this->teacher->createToken('something-else', [MobileDevice::ABILITY])->plainTextToken;

        $this->asDevice($token)->getJson('/api/mobile/attendance/sync/readiness')->assertForbidden();
    }

    // ---------------------------------------------------------------- admin

    public function test_only_admins_can_list_approve_or_revoke_devices(): void
    {
        $device = $this->device($this->teacher, 'pending');

        Sanctum::actingAs($this->teacher);
        $this->getJson('/api/admin/mobile-devices')->assertForbidden();
        $this->postJson("/api/admin/mobile-devices/{$device->id}/approve")->assertForbidden();
        $this->postJson("/api/admin/mobile-devices/{$device->id}/revoke")->assertForbidden();

        $this->assertSame('pending', $device->fresh()->status);
    }

    public function test_an_admin_sees_pending_devices_and_approves_one(): void
    {
        $device = $this->device($this->teacher, 'pending');
        $this->device($this->other, 'approved');

        $this->asAdmin();
        $this->getJson('/api/admin/mobile-devices?status=pending')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_name', 'Test phone')
            ->assertJsonPath('data.0.teacher.email', 'teacher@test.local');

        $this->postJson("/api/admin/mobile-devices/{$device->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/admin/mobile-devices/{$device->id}/approve")->assertOk();

        $this->assertSame($this->admin->id, $device->fresh()->approved_by);
        $this->assertSame(1, AuditLog::where('event', 'mobile_device.approved')->count());
        $this->flushHeaders();
        $this->login($this->teacher, $device->device_uuid)->assertOk()->assertJsonStructure(['token', 'expires_at']);
    }

    public function test_a_teacher_cannot_have_more_than_two_approved_devices(): void
    {
        $this->device($this->teacher, 'approved');
        $this->device($this->teacher, 'approved');
        $third = $this->device($this->teacher, 'pending');
        $removed = $this->device($this->teacher, 'revoked');

        $this->asAdmin();
        $this->postJson("/api/admin/mobile-devices/{$third->id}/approve")->assertUnprocessable();
        $this->postJson("/api/admin/mobile-devices/{$removed->id}/approve")->assertUnprocessable();

        $this->assertSame('pending', $third->fresh()->status);
        $this->assertSame('revoked', $removed->fresh()->status);
    }

    public function test_an_admin_can_reapprove_a_removed_device_and_teacher_can_sign_in_again(): void
    {
        $device = $this->device($this->teacher, 'approved');
        $token = $this->tokenFor($device);

        $this->asAdmin();
        $this->postJson("/api/admin/mobile-devices/{$device->id}/revoke")->assertOk()->assertJsonPath('data.status', 'revoked');

        $this->assertSame(0, PersonalAccessToken::count());
        $this->assertSame(1, AuditLog::where('event', 'mobile_device.revoked')->count());
        $this->asDevice($token)->getJson('/api/mobile/attendance/sync/readiness')->assertUnauthorized();
        $this->flushHeaders();
        $this->login($this->teacher, $device->device_uuid)->assertForbidden()->assertJsonPath('code', 'device_revoked');
        $this->assertSame('revoked', $device->fresh()->status);

        $this->asAdmin();
        $this->getJson('/api/admin/mobile-devices?status=revoked')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/admin/mobile-devices/{$device->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertNull($device->fresh()->revoked_at);
        $this->flushHeaders();
        $this->login($this->teacher, $device->device_uuid)->assertOk()->assertJsonStructure(['token', 'expires_at']);
    }

    // ---------------------------------------------------------------- helpers

    private function login(User $user, ?string $uuid = null, string $password = 'password')
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $user->email, 'password' => $password,
            'device_uuid' => $uuid ?? (string) Str::uuid(), 'device_name' => 'Test phone',
        ]);
    }

    private function device(User $user, string $status): MobileDevice
    {
        return MobileDevice::create([
            'user_id' => $user->id, 'device_uuid' => (string) Str::uuid(), 'name' => 'Test phone', 'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);
    }

    private function tokenFor(MobileDevice $device): string
    {
        return $device->user->createToken($device->tokenName(), [MobileDevice::ABILITY])->plainTextToken;
    }

    private function asDevice(string $token): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken($token);
    }

    private function asAdmin(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        Sanctum::actingAs($this->admin);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Intervention;
use App\Models\NotificationPreference;
use App\Models\ParentCommunication;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\InterventionService;
use App\Services\ParentCommunicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * In-app reminders: opt-in, counts only, calculated from the existing due queries.
 * "Today" is fixed at 2030-03-10 (school timezone). Needs a *_test MySQL database.
 */
class NotificationSummaryTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private User $admin;
    private ClassAssignment $class;
    private Student $student1;
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
        $classOther = ClassAssignment::create([
            'teacher_id' => $otherProfile->id, 'subject_id' => $science->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);

        $this->student1 = $this->student($this->class);
        Enrollment::create([
            'student_id' => $this->student1->id, 'class_assignment_id' => $classOther->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id,
        ]);

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ---------------------------------------------------------------- access and preferences

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/notifications/summary')->assertUnauthorized();
        $this->getJson('/api/notifications/preferences')->assertUnauthorized();
        $this->putJson('/api/notifications/preferences', [])->assertUnauthorized();
    }

    public function test_it_is_off_by_default_and_reveals_nothing(): void
    {
        $this->comm();
        $this->intervention();

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/notifications/summary')->assertOk()->assertExactJson(['data' => ['enabled' => false]]);
        $this->getJson('/api/notifications/preferences')->assertOk()
            ->assertExactJson(['data' => ['in_app_enabled' => false, 'include_students_to_review' => false]]);
    }

    public function test_preferences_save_and_validate(): void
    {
        Sanctum::actingAs($this->owner);

        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => false])
            ->assertOk()->assertJsonPath('data.in_app_enabled', true);
        $this->getJson('/api/notifications/preferences')->assertJsonPath('data.in_app_enabled', true);

        $this->putJson('/api/notifications/preferences', ['include_students_to_review' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['in_app_enabled']);
        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => 'maybe', 'include_students_to_review' => false])
            ->assertUnprocessable()->assertJsonValidationErrors(['in_app_enabled']);

        // Each teacher has their own switches.
        Sanctum::actingAs($this->other);
        $this->getJson('/api/notifications/preferences')->assertJsonPath('data.in_app_enabled', false);
    }

    public function test_admins_have_no_reminders(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/notifications/preferences')->assertForbidden();
        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => false])->assertForbidden();
        $this->getJson('/api/notifications/summary')->assertOk()->assertExactJson(['data' => ['enabled' => false]]);
    }

    public function test_changes_are_audited_only_when_something_changed(): void
    {
        Sanctum::actingAs($this->owner);
        $audits = fn (string $event) => AuditLog::where('auditable_type', NotificationPreference::class)->where('event', $event)->count();

        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => false])->assertOk();
        $this->assertSame(1, $audits('created'));

        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => false])->assertOk();
        $this->assertSame(1, $audits('created'));
        $this->assertSame(0, $audits('updated'));

        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => true])->assertOk();
        $this->assertSame(1, $audits('updated'));
    }

    // ---------------------------------------------------------------- the counts

    public function test_it_counts_the_owners_follow_ups_due_by_kind(): void
    {
        $this->enable($this->owner);
        $this->enable($this->other);

        $this->comm(['follow_up_date' => '2030-03-05']);        // overdue
        $this->comm(['follow_up_date' => '2030-03-10']);        // due today
        $this->comm(['follow_up_date' => '2030-03-12']);        // upcoming: not counted
        $this->intervention(['follow_up_date' => '2030-03-05']); // overdue
        $this->intervention(['follow_up_date' => '2030-03-10']); // due today

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/notifications/summary')->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.today', '2030-03-10')
            ->assertJsonPath('data.follow_ups_due.interventions', 2)
            ->assertJsonPath('data.follow_ups_due.parent_contacts', 2)
            ->assertJsonPath('data.follow_ups_due.total', 4)
            ->assertJsonPath('data.follow_ups_due.overdue', 2)
            ->assertJsonPath('data.students_to_review', null)
            ->assertJsonPath('data.badge_count', 4);

        // Someone else's records are not counted, even though they teach the same student.
        Sanctum::actingAs($this->other);
        $this->getJson('/api/notifications/summary')->assertOk()
            ->assertJsonPath('data.follow_ups_due.total', 0)
            ->assertJsonPath('data.badge_count', 0);
    }

    public function test_the_count_follows_the_record_and_the_class_rule(): void
    {
        $this->enable($this->owner);
        $student2 = $this->student($this->class);
        $c = $this->comm([], $this->owner, $student2);
        $this->intervention([], $student2);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/notifications/summary')->assertJsonPath('data.follow_ups_due.total', 2);

        app(ParentCommunicationService::class)->markFollowUpDone($this->owner, $c);
        $this->getJson('/api/notifications/summary')->assertJsonPath('data.follow_ups_due.total', 1);

        Enrollment::where('student_id', $student2->id)->where('class_assignment_id', $this->class->id)->delete();
        $this->getJson('/api/notifications/summary')->assertJsonPath('data.follow_ups_due.total', 0);
    }

    public function test_students_to_review_is_optional(): void
    {
        $this->enable($this->owner, false);
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/notifications/summary')->assertJsonPath('data.students_to_review', null);

        $this->enable($this->owner, true);
        $res = $this->getJson('/api/notifications/summary')->assertOk();
        $this->assertSame(0, $res->json('data.students_to_review'));
    }

    public function test_the_response_has_counts_only_and_no_text(): void
    {
        $this->enable($this->owner);
        $this->comm(['reason' => 'SECRET-REASON-2284', 'summary' => 'SECRET-SUMMARY-6620']);
        $this->intervention(['concern' => 'SECRET-CONCERN-7731']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/notifications/summary')->assertOk();

        $this->assertStringNotContainsString('SECRET', $res->getContent());
        $this->assertStringNotContainsString($this->student1->first_name, $res->getContent());
        $res->assertJsonStructure(['data' => [
            'enabled', 'today', 'students_to_review', 'badge_count',
            'follow_ups_due' => ['interventions', 'parent_contacts', 'total', 'overdue'],
        ]]);
    }

    public function test_the_query_count_does_not_grow_with_records(): void
    {
        $this->enable($this->owner);
        Sanctum::actingAs($this->owner);

        $queries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/notifications/summary')->assertOk();
            $sql = array_map(fn ($q) => preg_replace('/\?(,\s*\?)+/', '?', $q['query']), DB::getQueryLog());
            DB::disableQueryLog();

            return $sql;
        };

        foreach ([null, $this->class->id] as $classId) {
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }
        $this->getJson('/api/notifications/summary')->assertOk(); // warm-up
        $few = $queries();

        for ($k = 0; $k < 8; $k++) {
            $classId = $k % 2 ? $this->class->id : null;
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }

        $this->assertSame($few, $queries());
    }

    // ---------------------------------------------------------------- helpers

    private function enable(User $user, bool $review = false): void
    {
        Sanctum::actingAs($user);
        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => $review])->assertOk();
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function student(?ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'N-' . (++$this->seq), 'first_name' => 'Student' . $this->seq, 'last_name' => 'Test',
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

    private function comm(array $over = [], ?User $by = null, ?Student $student = null): ParentCommunication
    {
        return app(ParentCommunicationService::class)->create($by ?? $this->owner, $student ?? $this->student1, array_merge([
            'contact_date' => '2030-03-01', 'method' => 'phone_call', 'reason' => 'Missing work',
            'summary' => 'Spoke with the guardian', 'follow_up_date' => '2030-03-05',
        ], $over));
    }

    private function intervention(array $over = [], ?Student $student = null): Intervention
    {
        return app(InterventionService::class)->create($this->owner, $student ?? $this->student1, array_merge([
            'concern_type' => 'missing_work', 'concern' => 'Repeated missing assignments',
            'intervention_type' => 'consultation', 'intervention_date' => '2030-03-01',
            'follow_up_date' => '2030-03-05', 'notes' => null,
        ], $over));
    }
}

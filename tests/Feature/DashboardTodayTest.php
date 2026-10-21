<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Intervention;
use App\Models\ParentCommunication;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\InterventionService;
use App\Services\ParentCommunicationService;
use App\Services\StudentsToReviewService;
use App\Support\InterventionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Dashboard "Today" panel: counts only, same numbers as the bell and the due lists.
 * "Today" is fixed at 2030-03-10 (school timezone). Needs a *_test MySQL database.
 */
class DashboardTodayTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private User $idle;
    private User $admin;
    private ClassAssignment $class;
    private ClassAssignment $classOther;
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
        $this->idle = $this->makeUser('teacher', 'idle@test.local'); // teacher2: a profile but no classes
        $this->admin = $this->makeUser('admin', 'admin@test.local');
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);
        Teacher::create(['user_id' => $this->idle->id, 'employee_id' => 'T-IDLE']);

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

        $this->student1 = $this->student($this->class);
        Enrollment::create([
            'student_id' => $this->student1->id, 'class_assignment_id' => $this->classOther->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id,
        ]);

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ---------------------------------------------------------------- access

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/dashboard/today')->assertUnauthorized();
    }

    public function test_admins_get_applies_false_and_nothing_is_queried(): void
    {
        $this->comm();
        $this->intervention();

        Sanctum::actingAs($this->admin);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $res = $this->getJson('/api/dashboard/today');
        $sql = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $res->assertOk()->assertExactJson(['data' => ['applies' => false]]);
        foreach ($sql as $q) {
            $this->assertDoesNotMatchRegularExpression('/interventions|parent_communications|class_assignments|enrollments/', $q);
        }
    }

    // ---------------------------------------------------------------- ownership

    public function test_each_teacher_sees_only_their_own_counts(): void
    {
        $this->comm();
        $this->intervention();
        $this->comm([], $this->other);
        $this->comm([], $this->other);
        $this->intervention([], null, $this->other);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.applies', true)
            ->assertJsonPath('data.follow_ups_due.interventions', 1)
            ->assertJsonPath('data.follow_ups_due.parent_contacts', 1)
            ->assertJsonPath('data.follow_ups_due.total', 2);

        Sanctum::actingAs($this->other);
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.follow_ups_due.interventions', 1)
            ->assertJsonPath('data.follow_ups_due.parent_contacts', 2)
            ->assertJsonPath('data.follow_ups_due.total', 3);
    }

    public function test_a_teacher_without_classes_sees_zeros(): void
    {
        $this->comm();
        $this->intervention();

        Sanctum::actingAs($this->idle);
        $this->getJson('/api/dashboard/today')->assertOk()->assertExactJson(['data' => [
            'applies' => true,
            'today' => '2030-03-10',
            'follow_ups_due' => ['interventions' => 0, 'parent_contacts' => 0, 'total' => 0, 'overdue' => 0],
            'students_to_review' => 0,
            'active_interventions' => ['students' => 0, 'interventions' => 0, 'with_follow_up_due' => 0],
        ]]);
    }

    // ---------------------------------------------------------------- follow-ups due

    public function test_follow_ups_due_matches_the_due_services(): void
    {
        $this->comm(['follow_up_date' => '2030-03-05']);        // overdue
        $this->comm(['follow_up_date' => '2030-03-10']);        // due today
        $this->comm(['follow_up_date' => '2030-03-12']);        // upcoming: not counted
        $this->intervention(['follow_up_date' => '2030-03-05']); // overdue
        $this->intervention(['follow_up_date' => '2030-03-10']); // due today
        $this->intervention(['follow_up_date' => '2030-03-12']); // upcoming

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.today', '2030-03-10')
            ->assertJsonPath('data.follow_ups_due.interventions', 2)
            ->assertJsonPath('data.follow_ups_due.parent_contacts', 2)
            ->assertJsonPath('data.follow_ups_due.total', 4)
            ->assertJsonPath('data.follow_ups_due.overdue', 2);

        $this->assertSame(2, app(InterventionService::class)->due($this->owner, '2030-03-10')->count());
        $this->assertSame(2, app(ParentCommunicationService::class)->due($this->owner, '2030-03-10')->count());
    }

    public function test_the_midnight_boundary_uses_the_school_timezone(): void
    {
        $this->comm(['follow_up_date' => '2030-03-11']);
        $this->intervention(['follow_up_date' => '2030-03-11']);
        Sanctum::actingAs($this->owner);

        $this->travelTo(Carbon::parse('2030-03-10 23:59:00', self::TZ));
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.today', '2030-03-10')
            ->assertJsonPath('data.follow_ups_due.total', 0);

        $this->travelTo(Carbon::parse('2030-03-11 00:00:30', self::TZ));
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.today', '2030-03-11')
            ->assertJsonPath('data.follow_ups_due.total', 2)
            ->assertJsonPath('data.follow_ups_due.overdue', 0);
    }

    public function test_it_excludes_finished_deleted_foreign_archived_and_departed_records(): void
    {
        // Baseline: one valid record of each kind.
        $this->comm();
        $this->intervention();

        // Done / completed.
        $done = $this->comm();
        app(ParentCommunicationService::class)->markFollowUpDone($this->owner, $done);
        $completed = $this->intervention();
        Intervention::whereKey($completed->id)->update(['status' => InterventionStatus::COMPLETED]);

        // Deleted.
        $this->comm()->delete();
        $this->intervention()->delete();

        // Another author (same student).
        $this->comm([], $this->other);
        $this->intervention([], null, $this->other);

        // Archived class.
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $archived = ClassAssignment::create([
            'teacher_id' => $this->owner->teacher->id, 'subject_id' => $subject->id,
            'section_id' => $this->class->section_id, 'academic_year_id' => $this->class->academic_year_id, 'status' => 'active',
        ]);
        Enrollment::create(['student_id' => $this->student1->id, 'class_assignment_id' => $archived->id, 'section_id' => $archived->section_id, 'academic_year_id' => $archived->academic_year_id]);
        $this->comm(['class_assignment_id' => $archived->id]);
        $this->intervention(['class_assignment_id' => $archived->id]);
        $archived->delete();

        // A student who left the teacher's classes.
        $left = $this->student($this->class);
        $this->comm([], $this->owner, $left);
        $this->intervention([], $left);
        Enrollment::where('student_id', $left->id)->where('class_assignment_id', $this->class->id)->delete();

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.follow_ups_due.interventions', 1)
            ->assertJsonPath('data.follow_ups_due.parent_contacts', 1)
            ->assertJsonPath('data.follow_ups_due.total', 2);
    }

    // ---------------------------------------------------------------- students to review

    public function test_students_to_review_counts_only_students_needing_attention(): void
    {
        // The handled-prompt rules belong to StudentsToReviewService (own tests). Here we only check
        // that the panel counts needs_attention students and never the handled ones.
        $this->mock(StudentsToReviewService::class, function ($mock) {
            $mock->shouldReceive('forUser')->once()->andReturn(['students' => [
                ['student_id' => 1, 'needs_attention' => true],
                ['student_id' => 2, 'needs_attention' => false],
                ['student_id' => 3, 'needs_attention' => true],
            ]]);
        });

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/dashboard/today')->assertOk()->assertJsonPath('data.students_to_review', 2);
    }

    // ---------------------------------------------------------------- active interventions

    public function test_active_interventions_counts_open_ones_and_those_due(): void
    {
        $student2 = $this->student($this->class);

        $this->intervention(['follow_up_date' => '2030-03-05']);                       // planned, due
        $b = $this->intervention(['follow_up_date' => '2030-03-20']);                   // in progress, not due
        $c = $this->intervention(['follow_up_date' => null], $student2);                // requires follow-up, no date
        $d = $this->intervention(['follow_up_date' => '2030-03-05'], $student2);        // completed: excluded
        $this->intervention(['follow_up_date' => '2030-03-05'], null, $this->other);    // someone else's

        Intervention::whereKey($b->id)->update(['status' => 'in_progress']);
        Intervention::whereKey($c->id)->update(['status' => 'requires_follow_up']);
        Intervention::whereKey($d->id)->update(['status' => InterventionStatus::COMPLETED]);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/dashboard/today')->assertOk()
            ->assertJsonPath('data.active_interventions.students', 2)
            ->assertJsonPath('data.active_interventions.interventions', 3)
            ->assertJsonPath('data.active_interventions.with_follow_up_due', 1);

        // Same open set as the "Intervention active" indicator.
        $perStudent = app(StudentsToReviewService::class)->activeInterventions($this->owner);
        $this->assertSame(3, array_sum(array_column($perStudent, 'active_count')));
    }

    // ---------------------------------------------------------------- agreement with the bell

    public function test_it_agrees_with_the_bell(): void
    {
        $this->comm(['follow_up_date' => '2030-03-05']);
        $this->comm(['follow_up_date' => '2030-03-10']);
        $this->intervention(['follow_up_date' => '2030-03-05']);

        Sanctum::actingAs($this->owner);
        $this->putJson('/api/notifications/preferences', ['in_app_enabled' => true, 'include_students_to_review' => true])->assertOk();

        $panel = $this->getJson('/api/dashboard/today')->assertOk()->json('data');
        $bell = $this->getJson('/api/notifications/summary')->assertOk()->json('data');

        $this->assertSame($panel['follow_ups_due'], $bell['follow_ups_due']);
        $this->assertSame($panel['students_to_review'], $bell['students_to_review']);
        $this->assertSame($panel['follow_ups_due']['total'] + $panel['students_to_review'], $bell['badge_count']);
    }

    // ---------------------------------------------------------------- privacy and performance

    public function test_the_response_has_counts_only_and_no_text(): void
    {
        $this->comm(['reason' => 'SECRET-REASON-2284', 'summary' => 'SECRET-SUMMARY-6620']);
        $this->intervention(['concern' => 'SECRET-CONCERN-7731']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/dashboard/today')->assertOk();

        $this->assertStringNotContainsString('SECRET', $res->getContent());
        $this->assertStringNotContainsString($this->student1->first_name, $res->getContent());
        $this->assertStringNotContainsString($this->student1->last_name, $res->getContent());
        $res->assertJsonStructure(['data' => [
            'applies', 'today', 'students_to_review',
            'follow_ups_due' => ['interventions', 'parent_contacts', 'total', 'overdue'],
            'active_interventions' => ['students', 'interventions', 'with_follow_up_due'],
        ]]);
    }

    public function test_the_query_count_does_not_grow_with_records(): void
    {
        Sanctum::actingAs($this->owner);

        $queries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/dashboard/today')->assertOk();
            $sql = array_map(fn ($q) => preg_replace('/\?(,\s*\?)+/', '?', $q['query']), DB::getQueryLog());
            DB::disableQueryLog();

            return $sql;
        };

        foreach ([null, $this->class->id] as $classId) {
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }
        $this->getJson('/api/dashboard/today')->assertOk(); // warm-up
        $few = $queries();

        for ($k = 0; $k < 8; $k++) {
            $classId = $k % 2 ? $this->class->id : null;
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }

        $this->assertSame($few, $queries());
    }

    // ---------------------------------------------------------------- helpers

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

    private function intervention(array $over = [], ?Student $student = null, ?User $by = null): Intervention
    {
        return app(InterventionService::class)->create($by ?? $this->owner, $student ?? $this->student1, array_merge([
            'concern_type' => 'missing_work', 'concern' => 'Repeated missing assignments',
            'intervention_type' => 'consultation', 'intervention_date' => '2030-03-01',
            'follow_up_date' => '2030-03-05', 'notes' => null,
        ], $over));
    }
}

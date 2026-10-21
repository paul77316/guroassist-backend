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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Follow-ups due: parent-contact follow-ups join the list, and the "student still in your classes" rule
 * applies to both kinds. "Today" is fixed at 2030-03-10 (school timezone). Needs a *_test MySQL database.
 */
class DueFollowUpsTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private User $admin;
    private ClassAssignment $class;
    private Student $student1;   // enrolled in both classes
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

    // ---------------------------------------------------------------- communications in the due list

    public function test_a_communication_follow_up_is_due_at_midnight_manila_time(): void
    {
        $this->comm(['follow_up_date' => '2030-03-11']);

        $this->travelTo(Carbon::parse('2030-03-10 23:59:00', self::TZ));
        $this->assertCount(0, $this->comms()->due($this->owner));

        $this->travelTo(Carbon::parse('2030-03-11 00:00:00', self::TZ));
        $this->assertCount(1, $this->comms()->due($this->owner));

        // It stays due until it is marked done.
        $this->travelTo(Carbon::parse('2030-03-20 08:00:00', self::TZ));
        $this->assertCount(1, $this->comms()->due($this->owner));
    }

    public function test_due_communications_leave_out_done_deleted_other_authors_no_date_and_archived_classes(): void
    {
        // The owner gets a second active class so student1 stays theirs after the first one is archived.
        $english = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $class2 = ClassAssignment::create([
            'teacher_id' => Teacher::where('user_id', $this->owner->id)->value('id'), 'subject_id' => $english->id,
            'section_id' => $this->class->section_id, 'academic_year_id' => $this->class->academic_year_id, 'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $this->student1->id, 'class_assignment_id' => $class2->id,
            'section_id' => $this->class->section_id, 'academic_year_id' => $this->class->academic_year_id,
        ]);

        $keep = $this->comm();
        $done = $this->comm();
        $gone = $this->comm();
        $inClass = $this->comm(['class_assignment_id' => $this->class->id]);
        $this->comm(['follow_up_date' => null]);          // no follow-up date: never due
        $this->comm([], $this->other);                   // someone else's record

        $this->assertCount(4, $this->comms()->due($this->owner));

        $this->comms()->markFollowUpDone($this->owner, $done);
        $this->comms()->delete($this->owner, $gone);
        $this->assertEqualsCanonicalizing([$keep->id, $inClass->id], $this->comms()->due($this->owner)->pluck('id')->all());

        $this->class->update(['status' => 'archived']);
        $this->class->delete();
        $this->assertSame([$keep->id], $this->comms()->due($this->owner)->pluck('id')->all());

        $this->assertCount(0, $this->comms()->due($this->admin)); // admins have no records of their own
    }

    // ---------------------------------------------------------------- the class rule, both kinds

    public function test_both_kinds_leave_the_list_when_the_student_leaves_the_teachers_classes(): void
    {
        $student2 = $this->student($this->class);
        $this->comm([], $this->owner, $student2);
        $this->intervention([], $student2);

        $this->assertCount(1, $this->comms()->due($this->owner));
        $this->assertCount(1, app(InterventionService::class)->due($this->owner));

        Enrollment::where('student_id', $student2->id)->where('class_assignment_id', $this->class->id)->delete();

        $this->assertCount(0, $this->comms()->due($this->owner));
        $this->assertCount(0, app(InterventionService::class)->due($this->owner));
    }

    // ---------------------------------------------------------------- http

    public function test_the_due_endpoint_merges_both_kinds_without_free_text(): void
    {
        $this->intervention(['follow_up_date' => '2030-03-08', 'concern' => 'SECRET-CONCERN-7731']);
        $this->comm(['follow_up_date' => '2030-03-09', 'reason' => 'SECRET-REASON-2284', 'summary' => 'SECRET-SUMMARY-6620']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(2, 'data');

        $res->assertJsonPath('meta.today', '2030-03-10')
            ->assertJsonPath('data.0.kind', 'intervention')
            ->assertJsonPath('data.0.follow_up_state', 'overdue')
            ->assertJsonPath('data.1.kind', 'communication')
            ->assertJsonPath('data.1.method_label', 'Phone call')
            ->assertJsonPath('data.1.follow_up_state', 'overdue')
            ->assertJsonPath('data.1.student.full_name', $this->student1->full_name);
        $this->assertStringNotContainsString('SECRET', $res->getContent());

        Sanctum::actingAs($this->other);
        $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_marking_a_communication_done_removes_it_from_the_list(): void
    {
        $c = $this->comm();

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/follow-ups/due')->assertJsonCount(1, 'data');

        $this->postJson("/api/parent-communications/{$c->id}/follow-up-done")->assertOk();
        $this->getJson('/api/follow-ups/due')->assertJsonCount(0, 'data');

        $this->deleteJson("/api/parent-communications/{$c->id}/follow-up-done")->assertOk();
        $this->getJson('/api/follow-ups/due')->assertJsonCount(1, 'data');
    }

    public function test_the_due_endpoint_query_count_does_not_grow_with_records(): void
    {
        Sanctum::actingAs($this->owner);

        $queries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/follow-ups/due')->assertOk();
            $sql = array_map(fn ($q) => preg_replace('/\?(,\s*\?)+/', '?', $q['query']), DB::getQueryLog());
            DB::disableQueryLog();

            return $sql;
        };

        foreach ([null, $this->class->id] as $classId) {
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }
        $this->getJson('/api/follow-ups/due')->assertOk(); // warm-up
        $few = $queries();

        for ($k = 0; $k < 8; $k++) {
            $classId = $k % 2 ? $this->class->id : null;
            $this->comm(['class_assignment_id' => $classId]);
            $this->intervention(['class_assignment_id' => $classId]);
        }

        $this->assertSame($few, $queries());
    }

    // ---------------------------------------------------------------- helpers

    private function comms(): ParentCommunicationService
    {
        return app(ParentCommunicationService::class);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function student(?ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'F-' . (++$this->seq), 'first_name' => 'Student' . $this->seq, 'last_name' => 'Test',
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
        return $this->comms()->create($by ?? $this->owner, $student ?? $this->student1, array_merge([
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

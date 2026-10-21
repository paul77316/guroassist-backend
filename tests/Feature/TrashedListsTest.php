<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\ParentCommunication;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ParentCommunicationService;
use App\Services\StudentNoteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * The "Show deleted" lists of notes and parent communications: deleted records have their own list,
 * stay private to the author, can be restored, carry no full text, and stay hidden in an archived class.
 * "Today" is fixed at 2030-03-10 (school timezone). Needs a *_test MySQL database.
 */
class TrashedListsTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private ClassAssignment $class;   // the owner's first class
    private Student $student1;        // enrolled in both of the owner's classes and in the other teacher's class

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
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI']);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $make = fn (Teacher $t, Subject $s) => ClassAssignment::create([
            'teacher_id' => $t->id, 'subject_id' => $s->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);
        $this->class = $make($ownerProfile, $math);
        $class2 = $make($ownerProfile, $english); // keeps the student the owner's after $this->class is archived
        $classOther = $make($otherProfile, $science);

        $this->student1 = Student::create([
            'student_id' => 'T-1', 'first_name' => 'Student', 'last_name' => 'Test',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);
        foreach ([$this->class, $class2, $classOther] as $c) {
            Enrollment::create([
                'student_id' => $this->student1->id, 'class_assignment_id' => $c->id,
                'section_id' => $section->id, 'academic_year_id' => $year->id,
            ]);
        }

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    public function test_guests_are_rejected(): void
    {
        $sid = $this->student1->id;

        $this->getJson("/api/students/$sid/notes?trashed=only")->assertUnauthorized();
        $this->getJson("/api/students/$sid/parent-communications?trashed=only")->assertUnauthorized();
    }

    public function test_the_trashed_option_is_validated(): void
    {
        Sanctum::actingAs($this->owner);
        $sid = $this->student1->id;

        $this->getJson("/api/students/$sid/notes?trashed=all")->assertUnprocessable()->assertJsonValidationErrors(['trashed']);
        $this->getJson("/api/students/$sid/parent-communications?trashed=all")->assertUnprocessable()->assertJsonValidationErrors(['trashed']);
    }

    public function test_deleted_notes_have_their_own_list_and_can_be_restored(): void
    {
        $keep = $this->note('SECRET-KEEP-1111');
        $gone = $this->note('SECRET-GONE-2222');
        $url = "/api/students/{$this->student1->id}/notes";

        Sanctum::actingAs($this->owner);
        $this->deleteJson("/api/notes/{$gone->id}")->assertNoContent();

        $this->assertSame([$keep->id], $this->ids($this->getJson($url)->assertOk()));

        $trashed = $this->getJson("$url?trashed=only")->assertOk();
        $this->assertSame([$gone->id], $this->ids($trashed));
        $trashed->assertJsonPath('data.0.deleted', true);
        $this->assertArrayNotHasKey('body', $trashed->json('data.0')); // rows are a preview only, never the whole text

        // Another teacher who also teaches the student sees nothing, even of the deleted ones.
        Sanctum::actingAs($this->other);
        $this->getJson("$url?trashed=only")->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/notes/{$gone->id}/restore")->assertOk();
        $this->getJson("$url?trashed=only")->assertOk()->assertJsonCount(0, 'data');
        $this->assertEqualsCanonicalizing([$keep->id, $gone->id], $this->ids($this->getJson($url)->assertOk()));
    }

    public function test_deleted_communications_have_their_own_list_and_can_be_restored(): void
    {
        $keep = $this->comm();
        $gone = $this->comm();
        $url = "/api/students/{$this->student1->id}/parent-communications";

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/parent-communications/{$keep->id}/restore")->assertUnprocessable(); // not deleted
        $this->deleteJson("/api/parent-communications/{$gone->id}")->assertNoContent();

        $this->assertSame([$keep->id], $this->ids($this->getJson($url)->assertOk()));

        $trashed = $this->getJson("$url?trashed=only")->assertOk();
        $this->assertSame([$gone->id], $this->ids($trashed));
        $trashed->assertJsonPath('data.0.deleted', true);
        $this->assertArrayNotHasKey('reason', $trashed->json('data.0'));
        $this->assertArrayNotHasKey('summary', $trashed->json('data.0'));

        Sanctum::actingAs($this->other);
        $this->getJson("$url?trashed=only")->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/parent-communications/{$gone->id}/restore")->assertOk()->assertJsonPath('data.deleted', false);
        $this->getJson("$url?trashed=only")->assertOk()->assertJsonCount(0, 'data');
        $this->assertEqualsCanonicalizing([$keep->id, $gone->id], $this->ids($this->getJson($url)->assertOk()));
    }

    public function test_deleted_records_of_an_archived_class_stay_hidden(): void
    {
        $noteGeneral = $this->note('general');
        $noteInClass = $this->note('in class', $this->class->id);
        $commGeneral = $this->comm();
        $commInClass = $this->comm($this->class->id);

        Sanctum::actingAs($this->owner);
        foreach ([$noteGeneral, $noteInClass] as $n) {
            $this->deleteJson("/api/notes/{$n->id}")->assertNoContent();
        }
        foreach ([$commGeneral, $commInClass] as $c) {
            $this->deleteJson("/api/parent-communications/{$c->id}")->assertNoContent();
        }

        $notes = "/api/students/{$this->student1->id}/notes?trashed=only";
        $comms = "/api/students/{$this->student1->id}/parent-communications?trashed=only";
        $this->assertCount(2, $this->getJson($notes)->json('data'));
        $this->assertCount(2, $this->getJson($comms)->json('data'));

        $this->class->update(['status' => 'archived']);
        $this->class->delete();

        $this->assertSame([$noteGeneral->id], $this->ids($this->getJson($notes)->assertOk()));
        $this->assertSame([$commGeneral->id], $this->ids($this->getJson($comms)->assertOk()));

        // Nothing was lost: restoring the class brings the deleted records back to the list.
        $this->class->restore();
        $this->class->update(['status' => 'active']);
        $this->assertCount(2, $this->getJson($notes)->json('data'));
        $this->assertCount(2, $this->getJson($comms)->json('data'));
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function ids($response): array
    {
        return array_column($response->json('data'), 'id');
    }

    private function note(string $body, ?int $classId = null): StudentNote
    {
        return app(StudentNoteService::class)->create($this->owner, $this->student1, ['body' => $body, 'class_assignment_id' => $classId]);
    }

    private function comm(?int $classId = null): ParentCommunication
    {
        return app(ParentCommunicationService::class)->create($this->owner, $this->student1, [
            'contact_date' => '2030-03-01', 'method' => 'phone_call', 'reason' => 'SECRET-REASON-5521',
            'summary' => 'SECRET-SUMMARY-7743', 'follow_up_date' => null, 'class_assignment_id' => $classId,
        ]);
    }
}

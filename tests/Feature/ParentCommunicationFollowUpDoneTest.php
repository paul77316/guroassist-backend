<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\ParentCommunication;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ParentCommunicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Mark done and Undo for the follow-up of a parent communication.
 * "Today" is fixed at 2030-03-10 (school timezone). Needs a *_test MySQL database.
 */
class ParentCommunicationFollowUpDoneTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
    private User $admin;
    private ClassAssignment $class;
    private Student $student;

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

        $this->student = Student::create([
            'student_id' => 'D-1', 'first_name' => 'Student', 'last_name' => 'Test',
            'sex' => 'male', 'date_of_birth' => '2015-01-01', 'status' => 'active',
        ]);
        foreach ([$this->class, $classOther] as $c) {
            Enrollment::create([
                'student_id' => $this->student->id, 'class_assignment_id' => $c->id,
                'section_id' => $section->id, 'academic_year_id' => $year->id,
            ]);
        }

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ---------------------------------------------------------------- service rules

    public function test_marking_done_stamps_today_and_is_audited(): void
    {
        $c = $this->comm();

        $done = $this->svc()->markFollowUpDone($this->owner, $c);

        $this->assertSame('2030-03-10', $done->fresh()->follow_up_done_at->format('Y-m-d'));
        $this->assertSame(1, AuditLog::where('event', 'follow_up_done')->count());
    }

    public function test_it_needs_a_follow_up_date(): void
    {
        $c = $this->comm(['follow_up_date' => null]);

        $this->assertFails(fn () => $this->svc()->markFollowUpDone($this->owner, $c), 'follow_up_date');
        $this->assertNull($c->fresh()->follow_up_done_at);
    }

    public function test_it_cannot_be_marked_twice(): void
    {
        $c = $this->comm();
        $this->svc()->markFollowUpDone($this->owner, $c);
        $audits = AuditLog::count();

        $this->assertFails(fn () => $this->svc()->markFollowUpDone($this->owner, $c), 'follow_up_done_at');
        $this->assertSame($audits, AuditLog::count());
    }

    public function test_undo_clears_the_done_date_and_is_audited(): void
    {
        $c = $this->comm();

        $this->assertFails(fn () => $this->svc()->undoFollowUpDone($this->owner, $c), 'follow_up_done_at'); // not done yet

        $this->svc()->markFollowUpDone($this->owner, $c);
        $this->svc()->undoFollowUpDone($this->owner, $c);

        $this->assertNull($c->fresh()->follow_up_done_at);
        $this->assertSame(1, AuditLog::where('event', 'follow_up_undone')->count());
    }

    public function test_changing_or_removing_the_follow_up_date_clears_the_done_date(): void
    {
        $c = $this->comm();
        $this->svc()->markFollowUpDone($this->owner, $c);

        // Same date, other fields edited: stays done.
        $this->svc()->update($this->owner, $c->fresh(), $this->data(['reason' => 'Another reason']));
        $this->assertNotNull($c->fresh()->follow_up_done_at);

        // Date key not sent: stays done.
        $noDate = $this->data();
        unset($noDate['follow_up_date']);
        $this->svc()->update($this->owner, $c->fresh(), $noDate);
        $this->assertNotNull($c->fresh()->follow_up_done_at);

        // New date: cleared.
        $this->svc()->update($this->owner, $c->fresh(), $this->data(['follow_up_date' => '2030-03-20']));
        $this->assertNull($c->fresh()->follow_up_done_at);

        // Removed date: cleared too.
        $this->svc()->markFollowUpDone($this->owner, $c->fresh());
        $this->svc()->update($this->owner, $c->fresh(), $this->data(['follow_up_date' => null]));
        $this->assertNull($c->fresh()->follow_up_done_at);
        $this->assertNull($c->fresh()->follow_up_date);
    }

    public function test_an_admin_doing_it_is_a_correction(): void
    {
        $c = $this->comm();

        $this->svc()->markFollowUpDone($this->admin, $c);
        $this->svc()->undoFollowUpDone($this->admin, $c);

        $this->assertSame(2, AuditLog::where('event', 'admin_corrected')->count());
        $this->assertSame(0, AuditLog::where('event', 'follow_up_done')->count());
    }

    public function test_deleted_and_archived_records_cannot_be_changed(): void
    {
        $gone = $this->comm();
        $this->svc()->delete($this->owner, $gone);
        $this->assertFails(fn () => $this->svc()->markFollowUpDone($this->owner, $gone), 'communication');

        $inClass = $this->comm(['class_assignment_id' => $this->class->id]);
        $this->class->update(['status' => 'archived']);
        $this->class->delete();
        $this->assertFails(fn () => $this->svc()->markFollowUpDone($this->admin, $inClass), 'communication');
        $this->assertNull($inClass->fresh()->follow_up_done_at);
    }

    public function test_no_free_text_reaches_the_audit_log(): void
    {
        $c = $this->comm(['reason' => 'SECRET-REASON-5521', 'summary' => 'SECRET-SUMMARY-7743']);
        $this->svc()->markFollowUpDone($this->owner, $c);
        $this->svc()->undoFollowUpDone($this->owner, $c);
        $this->svc()->update($this->owner, $c->fresh(), $this->data(['follow_up_date' => '2030-03-22']));

        $everything = AuditLog::all()->map(fn ($r) => json_encode([$r->old_values, $r->new_values]))->implode(' ');
        $this->assertStringNotContainsString('SECRET-REASON-5521', $everything);
        $this->assertStringNotContainsString('SECRET-SUMMARY-7743', $everything);
        $this->assertStringContainsString('follow_up_done_changed', $everything);
    }

    // ---------------------------------------------------------------- http

    public function test_guests_are_rejected(): void
    {
        $c = $this->comm();

        $this->postJson("/api/parent-communications/{$c->id}/follow-up-done")->assertStatus(401);
        $this->deleteJson("/api/parent-communications/{$c->id}/follow-up-done")->assertStatus(401);
    }

    public function test_mark_done_and_undo_over_http(): void
    {
        $c = $this->comm();
        $url = "/api/parent-communications/{$c->id}/follow-up-done";

        Sanctum::actingAs($this->owner);
                $this->postJson($url)->assertStatus(200)
            ->assertJsonPath('data.follow_up_done_at', '2030-03-10')
            ->assertJsonPath('data.follow_up_state', 'done');
        $this->assertSame('2030-03-10', $c->fresh()->follow_up_done_at->format('Y-m-d'));

        $this->postJson($url)->assertStatus(422)->assertJsonValidationErrors('follow_up_done_at');

        $this->deleteJson($url)->assertStatus(200);
        $this->assertNull($c->fresh()->follow_up_done_at);

        $this->deleteJson($url)->assertStatus(422)->assertJsonValidationErrors('follow_up_done_at');
    }

    public function test_only_the_author_and_admins_reach_the_endpoints(): void
    {
        $c = $this->comm();
        $url = "/api/parent-communications/{$c->id}/follow-up-done";

        Sanctum::actingAs($this->other); // teaches the same student, did not write the record
        $this->postJson($url)->assertStatus(403);
        $this->assertNull($c->fresh()->follow_up_done_at);

        Sanctum::actingAs($this->admin);
        $this->postJson($url)->assertStatus(200);
        $this->assertSame(1, AuditLog::where('event', 'admin_corrected')->count());
    }

    public function test_an_archived_class_blocks_the_endpoints(): void
    {
        $c = $this->comm(['class_assignment_id' => $this->class->id]);
        $this->class->update(['status' => 'archived']);
        $this->class->delete();

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/parent-communications/{$c->id}/follow-up-done")->assertStatus(403);
    }

    public function test_the_follow_up_state_is_calculated_for_the_school_today(): void
    {
        Sanctum::actingAs($this->owner);

        foreach ([['2030-03-08', 'overdue'], ['2030-03-10', 'due_today'], ['2030-03-12', 'upcoming'], [null, null]] as [$date, $state]) {
            $c = $this->comm(['follow_up_date' => $date]);
            $this->getJson("/api/parent-communications/{$c->id}")
                ->assertOk()
                ->assertJsonPath('data.follow_up_state', $state);
        }
    }

    // ---------------------------------------------------------------- helpers

    private function svc(): ParentCommunicationService
    {
        return app(ParentCommunicationService::class);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function data(array $over = []): array
    {
        return array_merge([
            'contact_date' => '2030-03-01', 'method' => 'phone_call', 'reason' => 'Missing work',
            'summary' => 'Spoke with the guardian', 'follow_up_date' => '2030-03-08',
        ], $over);
    }

    private function comm(array $over = []): ParentCommunication
    {
        return $this->svc()->create($this->owner, $this->student, $this->data($over));
    }

    private function assertFails(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }
}

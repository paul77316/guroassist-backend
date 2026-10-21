<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Intervention;
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
 * The HTTP layer of notes, interventions, follow-ups and parent communications:
 * status codes, route binding, validation, privacy and what the list endpoints leave out.
 * "Today" is fixed at 2030-03-10 (school timezone). Refuses to run unless the database is a *_test MySQL database.
 */
class InterventionApiTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;      // teacher who owns $class
    private User $other;      // teacher of $classOther; also teaches student1
    private User $outsider;   // teacher with no classes
    private User $admin;
    private ClassAssignment $class;
    private ClassAssignment $classOther;
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
        $this->outsider = $this->makeUser('teacher', 'outsider@test.local');
        $this->admin = $this->makeUser('admin', 'admin@test.local');
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-OWNER']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-OTHER']);
        Teacher::create(['user_id' => $this->outsider->id, 'employee_id' => 'T-OUT']);

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

    // ================================================================ guests and options

    public function test_guests_are_rejected_everywhere(): void
    {
        $sid = $this->student1->id;

        foreach ([
            ['getJson', "/api/students/$sid/interventions"],
            ['postJson', "/api/students/$sid/interventions"],
            ['getJson', '/api/interventions/1'],
            ['putJson', '/api/interventions/1'],
            ['deleteJson', '/api/interventions/1'],
            ['postJson', '/api/interventions/1/restore'],
            ['postJson', '/api/interventions/1/followups'],
            ['getJson', '/api/follow-ups/due'],
            ['getJson', '/api/intervention-options'],
            ['getJson', "/api/students/$sid/notes"],
            ['getJson', '/api/notes/1'],
            ['getJson', "/api/students/$sid/parent-communications"],
            ['getJson', '/api/parent-communications/1'],
        ] as [$method, $url]) {
            $this->$method($url)->assertUnauthorized();
        }
    }

    public function test_options_lists_the_neutral_vocabularies(): void
    {
        Sanctum::actingAs($this->owner);

        $res = $this->getJson('/api/intervention-options')->assertOk();

        $this->assertCount(4, $res->json('data.statuses'));
        $this->assertContains('missing_work', array_column($res->json('data.concern_types'), 'value'));
        $this->assertContains('Requires Follow-up', array_column($res->json('data.statuses'), 'label'));
    }

    // ================================================================ create and validation

    public function test_a_teacher_creates_an_intervention_and_cannot_forge_server_fields(): void
    {
        Sanctum::actingAs($this->owner);

        $res = $this->postJson("/api/students/{$this->student1->id}/interventions", $this->payload([
            'author_id' => $this->other->id, 'status' => 'completed', 'outcome' => 'improved', 'student_id' => 999999,
        ]))->assertCreated();

        $res->assertJsonPath('data.status', 'planned')->assertJsonPath('data.outcome', null);
        $row = Intervention::findOrFail($res->json('data.id'));
        $this->assertSame($this->owner->id, $row->author_id);
        $this->assertSame($this->student1->id, $row->student_id);

        // Editing cannot change them either, and cannot move the record to another class.
        $this->putJson("/api/interventions/{$row->id}", $this->payload([
            'status' => 'completed', 'author_id' => $this->other->id, 'class_assignment_id' => $this->classOther->id,
        ]))->assertOk();
        $row->refresh();
        $this->assertSame('planned', $row->status);
        $this->assertSame($this->owner->id, $row->author_id);
        $this->assertNull($row->class_assignment_id);
    }

    public function test_creating_validates_the_payload(): void
    {
        Sanctum::actingAs($this->owner);
        $url = "/api/students/{$this->student1->id}/interventions";

        $this->postJson($url, $this->payload(['concern_type' => 'villain']))->assertUnprocessable()->assertJsonValidationErrors(['concern_type']);
        $this->postJson($url, $this->payload(['concern' => '']))->assertUnprocessable()->assertJsonValidationErrors(['concern']);
        $this->postJson($url, $this->payload(['intervention_date' => '10/03/2030']))->assertUnprocessable()->assertJsonValidationErrors(['intervention_date']);
        $this->postJson($url, $this->payload(['follow_up_date' => '2030-02-01']))->assertUnprocessable()->assertJsonValidationErrors(['follow_up_date']);
        // Another teacher's class: rejected with one neutral message.
        $this->postJson($url, $this->payload(['class_assignment_id' => $this->classOther->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['class_assignment_id']);

        $this->assertSame(0, Intervention::count());
    }

    public function test_a_teacher_cannot_create_for_a_student_who_is_not_theirs(): void
    {
        Sanctum::actingAs($this->outsider);

        $this->postJson("/api/students/{$this->student1->id}/interventions", $this->payload())->assertForbidden();
        $this->assertSame(0, Intervention::count());
    }

    // ================================================================ privacy matrix

    public function test_only_the_author_and_admins_reach_a_record(): void
    {
        $i = $this->make();

        foreach ([$this->other, $this->outsider] as $stranger) {
            Sanctum::actingAs($stranger);
            $this->getJson("/api/interventions/{$i->id}")->assertForbidden();
            $this->putJson("/api/interventions/{$i->id}", $this->payload())->assertForbidden();
            $this->deleteJson("/api/interventions/{$i->id}")->assertForbidden();
            $this->postJson("/api/interventions/{$i->id}/restore")->assertForbidden();
            $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('in_progress'))->assertForbidden();
        }

        // The other teacher also teaches this student, so the list is allowed but empty.
        Sanctum::actingAs($this->other);
        $this->getJson("/api/students/{$this->student1->id}/interventions")->assertOk()->assertJsonCount(0, 'data');

        // A teacher who does not teach the student is refused the list itself.
        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/students/{$this->student1->id}/interventions")->assertForbidden();

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/interventions/{$i->id}")->assertOk();
        Sanctum::actingAs($this->admin);
        $this->getJson("/api/interventions/{$i->id}")->assertOk();
    }

    public function test_lists_and_due_rows_carry_no_free_text_but_the_detail_does(): void
    {
        $i = $this->make(['concern' => 'SECRET-CONCERN-7731', 'notes' => 'SECRET-NOTES-4410', 'follow_up_date' => '2030-03-10']);
        Sanctum::actingAs($this->owner);
        // Entry dated yesterday with the next follow-up today, so the record is still due when the lists are read.
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('in_progress', [
            'followup_date' => '2030-03-09', 'next_follow_up_date' => '2030-03-10',
            'progress' => 'SECRET-PROGRESS-9082', 'notes' => 'SECRET-REASON-1156',
        ]))->assertCreated();

        foreach ([
            "/api/students/{$this->student1->id}/interventions",
            '/api/follow-ups/due',
        ] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();
            $this->assertStringContainsString('"id"', $body);
            foreach (['SECRET-CONCERN-7731', 'SECRET-NOTES-4410', 'SECRET-PROGRESS-9082', 'SECRET-REASON-1156'] as $secret) {
                $this->assertStringNotContainsString($secret, $body, "$secret leaked into $url");
            }
        }

        $detail = $this->getJson("/api/interventions/{$i->id}")->assertOk();
        $this->assertSame('SECRET-CONCERN-7731', $detail->json('data.concern'));
        $this->assertSame('SECRET-PROGRESS-9082', $detail->json('data.followups.0.progress'));
        $this->assertSame('Individual consultation', $detail->json('data.intervention_type_label'));
    }

    // ================================================================ follow-up entries

    public function test_the_follow_up_flow_over_http(): void
    {
        $i = $this->make();
        Sanctum::actingAs($this->owner);

        $res = $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('in_progress'))->assertCreated();
        $res->assertJsonPath('data.status', 'in_progress')->assertJsonCount(1, 'data.followups');
        $this->assertContains('requires_follow_up', array_column($res->json('data.next_statuses'), 'value'));

        // Prohibited transition, missing outcome and a stale request are all 422 with a field error.
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('planned'))
            ->assertUnprocessable()->assertJsonValidationErrors(['to_status']);
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('completed', ['outcome' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors(['outcome']);
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('completed', ['expected_status' => 'planned']))
            ->assertUnprocessable()->assertJsonValidationErrors(['status']);
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('nonsense'))
            ->assertUnprocessable()->assertJsonValidationErrors(['to_status']);

        $this->assertSame('in_progress', $i->fresh()->status);
        $this->assertSame(1, $i->followups()->count());
    }

    public function test_completing_and_reopening_over_http(): void
    {
        $i = $this->make();
        Sanctum::actingAs($this->owner);

        $done = $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('completed'))->assertCreated();
        $done->assertJsonPath('data.status', 'completed')->assertJsonPath('data.follow_up_date', null)->assertJsonPath('data.follow_up_state', null);
        $done->assertJsonPath('data.followups.0.outcome', 'improved')->assertJsonPath('data.followups.0.outcome_label', 'Improved');
        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('in_progress', ['notes' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['notes']);

        $this->postJson("/api/interventions/{$i->id}/followups", $this->entry('in_progress'))
            ->assertCreated()->assertJsonPath('data.status', 'in_progress')->assertJsonPath('data.outcome', null);

        $this->assertSame(['completed', 'reopened'], $i->followups()->pluck('entry_type')->all());
        $this->assertSame(1, AuditLog::where('event', 'reopened')->count());
    }

    // ================================================================ due follow-ups

    public function test_the_due_list_follows_the_school_calendar(): void
    {
        $this->make(['follow_up_date' => '2030-03-11']);
        Sanctum::actingAs($this->owner);

        $this->travelTo(Carbon::parse('2030-03-10 23:59:00', self::TZ));
        $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(0, 'data');

        $this->travelTo(Carbon::parse('2030-03-11 00:00:00', self::TZ));
        $res = $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(1, 'data');
        $res->assertJsonPath('data.0.follow_up_state', 'due_today')->assertJsonPath('meta.today', '2030-03-11');
        $this->assertSame($this->student1->full_name, $res->json('data.0.student.full_name'));

        $this->travelTo(Carbon::parse('2030-03-14 08:00:00', self::TZ));
        $this->getJson('/api/follow-ups/due')->assertJsonPath('data.0.follow_up_state', 'overdue');

        Sanctum::actingAs($this->other);
        $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(0, 'data');
    }

    // ================================================================ archived classes

    public function test_records_of_an_archived_class_disappear_for_everyone(): void
    {
        $i = $this->make(['class_assignment_id' => $this->class->id, 'follow_up_date' => '2030-03-08']);
        Sanctum::actingAs($this->owner);
        $this->getJson("/api/interventions/{$i->id}")->assertOk()->assertJsonPath('data.class.subject', 'Mathematics');

        $this->class->update(['status' => 'archived']);
        $this->class->delete();

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/interventions/{$i->id}")->assertForbidden();
        $this->getJson('/api/follow-ups/due')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->admin);
        $this->getJson("/api/interventions/{$i->id}")->assertForbidden();
        $this->putJson("/api/interventions/{$i->id}", $this->payload())->assertForbidden();
        $this->getJson("/api/students/{$this->student1->id}/interventions")->assertOk()->assertJsonCount(0, 'data');
    }

    // ================================================================ delete and restore

    public function test_delete_and_restore_over_http(): void
    {
        $i = $this->make();
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/interventions/{$i->id}/restore")->assertUnprocessable(); // not deleted
        $this->deleteJson("/api/interventions/{$i->id}")->assertNoContent();
        $this->getJson("/api/interventions/{$i->id}")->assertNotFound();
        $this->getJson("/api/students/{$this->student1->id}/interventions?trashed=only")->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($this->other);
        $this->postJson("/api/interventions/{$i->id}/restore")->assertForbidden();

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/interventions/{$i->id}/restore")->assertOk()->assertJsonPath('data.deleted', false);
        $this->getJson("/api/interventions/{$i->id}")->assertOk();
    }

    // ================================================================ admin read audit

    public function test_an_admin_reading_a_record_is_audited_without_text(): void
    {
        $i = $this->make(['concern' => 'SECRET-CONCERN-7731']);

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/interventions/{$i->id}")->assertOk();
        $this->assertSame(0, AuditLog::where('event', 'admin_read')->count());

        Sanctum::actingAs($this->admin);
        $this->getJson("/api/interventions/{$i->id}")->assertOk();

        $rows = AuditLog::where('event', 'admin_read')->get();
        $this->assertCount(1, $rows);
        $this->assertStringContainsString((string) $i->id, json_encode($rows->first()->new_values));
        $this->assertStringNotContainsString('SECRET-CONCERN-7731', json_encode($rows->first()->new_values));
    }

    // ================================================================ notes

    public function test_notes_are_private_to_the_author_and_lists_show_only_a_preview(): void
    {
        Sanctum::actingAs($this->owner);
        $long = str_repeat('word ', 80) . 'SECRET-TAIL-5521';

        $id = $this->postJson("/api/students/{$this->student1->id}/notes", ['body' => $long])
            ->assertCreated()->assertJsonPath('data.body', $long)->json('data.id');

        $list = $this->getJson("/api/students/{$this->student1->id}/notes")->assertOk();
        $this->assertStringNotContainsString('SECRET-TAIL-5521', $list->getContent());
        $this->assertLessThanOrEqual(103, mb_strlen($list->json('data.0.preview')));
        $this->getJson("/api/notes/$id")->assertOk()->assertJsonPath('data.body', $long);

        $this->putJson("/api/notes/$id", ['body' => 'edited'])->assertOk()->assertJsonPath('data.body', 'edited');
        $this->postJson("/api/students/{$this->student1->id}/notes", ['body' => ''])->assertUnprocessable();

        Sanctum::actingAs($this->other);
        $this->getJson("/api/notes/$id")->assertForbidden();
        $this->putJson("/api/notes/$id", ['body' => 'x'])->assertForbidden();
        $this->deleteJson("/api/notes/$id")->assertForbidden();
        $this->getJson("/api/students/{$this->student1->id}/notes")->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->admin);
        $this->getJson("/api/notes/$id")->assertOk();
        $this->assertSame(1, AuditLog::where('event', 'admin_read')->count());

        Sanctum::actingAs($this->owner);
        $this->deleteJson("/api/notes/$id")->assertNoContent();
        $this->getJson("/api/notes/$id")->assertNotFound();
        $this->postJson("/api/notes/$id/restore")->assertOk();
    }

    // ================================================================ parent communications

    public function test_parent_communications_over_http(): void
    {
        Sanctum::actingAs($this->owner);
        $url = "/api/students/{$this->student1->id}/parent-communications";
        $data = [
            'contact_date' => '2030-03-10', 'method' => 'phone_call',
            'reason' => 'SECRET-REASON-2284', 'summary' => 'SECRET-SUMMARY-6620', 'follow_up_date' => '2030-03-17',
        ];

        $id = $this->postJson($url, $data)->assertCreated()->assertJsonPath('data.method_label', 'Phone call')->json('data.id');

        $this->postJson($url, array_merge($data, ['contact_date' => '2030-03-11']))->assertUnprocessable()->assertJsonValidationErrors(['contact_date']);
        $this->postJson($url, array_merge($data, ['method' => 'carrier_pigeon']))->assertUnprocessable()->assertJsonValidationErrors(['method']);
        $this->postJson($url, array_merge($data, ['follow_up_date' => '2030-03-01']))->assertUnprocessable()->assertJsonValidationErrors(['follow_up_date']);

        $list = $this->getJson($url)->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringNotContainsString('SECRET', $list->getContent());
        $this->getJson("/api/parent-communications/$id")->assertOk()->assertJsonPath('data.summary', 'SECRET-SUMMARY-6620');

        Sanctum::actingAs($this->other);
        $this->getJson("/api/parent-communications/$id")->assertForbidden();
        $this->putJson("/api/parent-communications/$id", $data)->assertForbidden();
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
    }

    // ================================================================ queries

    public function test_the_student_list_query_count_does_not_grow_with_records(): void
    {
        Sanctum::actingAs($this->owner);
        $url = "/api/students/{$this->student1->id}/interventions";

        $measure = function () use ($url) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson($url)->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        foreach ([null, $this->class->id] as $classId) {
            $this->make(['class_assignment_id' => $classId]);
        }
        $few = $measure();

        for ($k = 0; $k < 8; $k++) {
            $this->make(['class_assignment_id' => $k % 2 ? $this->class->id : null]);
        }

        $this->assertSame($few, $measure());
    }

    // ================================================================ cross-student list

    public function test_guests_cannot_list_interventions(): void
    {
        $this->getJson('/api/interventions')->assertUnauthorized();
    }

    public function test_the_list_shows_only_the_authors_interventions_across_students(): void
    {
        $student2 = $this->student($this->class);
        $a = $this->make();
        $b = app(InterventionService::class)->create($this->owner, $student2, $this->payload());

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/interventions')->assertOk();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], array_column($res->json('data'), 'id'));
        $res->assertJsonPath('meta.total', 2);

        // The other teacher also teaches student1 but wrote nothing; admins have no records of their own.
        Sanctum::actingAs($this->other);
        $this->getJson('/api/interventions')->assertOk()->assertJsonCount(0, 'data');
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/interventions')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_list_filters_by_status_and_shows_the_completion_date(): void
    {
        $open = $this->make(['concern' => 'SECRET-CONCERN-7731']);
        $done = $this->make();
        app(InterventionService::class)->addEntry($this->owner, $done, [
            'followup_date' => '2030-03-10', 'to_status' => 'completed', 'outcome' => 'partly_improved',
        ]);

        Sanctum::actingAs($this->owner);

        $completed = $this->getJson('/api/interventions?status=completed')->assertOk();
        $this->assertSame([$done->id], array_column($completed->json('data'), 'id'));
        $completed->assertJsonPath('data.0.completed_on', '2030-03-10')
            ->assertJsonPath('data.0.outcome_label', 'Partly improved')
            ->assertJsonPath('data.0.student.full_name', $this->student1->full_name);

        $active = $this->getJson('/api/interventions?status=active')->assertOk();
        $this->assertSame([$open->id], array_column($active->json('data'), 'id'));
        $active->assertJsonPath('data.0.completed_on', null);

        $this->getJson('/api/interventions?status=planned')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/interventions?status=in_progress')->assertOk()->assertJsonCount(0, 'data');

        // Rows carry no free text.
        $this->assertStringNotContainsString('SECRET-CONCERN-7731', $this->getJson('/api/interventions')->getContent());
    }

    public function test_the_list_filters_by_class_concern_type_and_student(): void
    {
        $student2 = $this->student($this->class);
        $withClass = $this->make(['class_assignment_id' => $this->class->id]);
        $general = $this->make(['concern_type' => 'attendance_concern']);
        $second = app(InterventionService::class)->create($this->owner, $student2, $this->payload());

        Sanctum::actingAs($this->owner);
        $ids = fn (string $query) => array_column($this->getJson("/api/interventions$query")->assertOk()->json('data'), 'id');

        $this->assertSame([$withClass->id], $ids('?class_assignment_id=' . $this->class->id));
        $this->assertSame([$general->id], $ids('?concern_type=attendance_concern'));
        $this->assertSame([$second->id], $ids('?search=' . urlencode($student2->first_name)));
        $this->assertSame([$second->id], $ids('?search=' . urlencode($student2->student_id)));
        $this->assertEqualsCanonicalizing([$withClass->id, $general->id], $ids('?search=' . urlencode($this->student1->full_name)));
        $this->assertSame([], $ids('?search=nobody'));
    }

    public function test_the_list_is_paged(): void
    {
        for ($k = 0; $k < 27; $k++) {
            $this->make();
        }

        Sanctum::actingAs($this->owner);
        $first = $this->getJson('/api/interventions')->assertOk();
        $this->assertCount(25, $first->json('data'));
        $first->assertJsonPath('meta.total', 27)->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/interventions?page=2')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_the_list_validates_its_filters(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/interventions?status=nonsense')->assertUnprocessable()->assertJsonValidationErrors(['status']);
        $this->getJson('/api/interventions?concern_type=villain')->assertUnprocessable()->assertJsonValidationErrors(['concern_type']);
        $this->getJson('/api/interventions?outcome=great')->assertUnprocessable()->assertJsonValidationErrors(['outcome']);
    }

    public function test_the_list_leaves_out_students_the_teacher_no_longer_has_and_archived_classes(): void
    {
        $inClass = $this->make(['class_assignment_id' => $this->class->id]);
        $gone = $this->student($this->class);
        $lost = app(InterventionService::class)->create($this->owner, $gone, $this->payload());

        Sanctum::actingAs($this->owner);
        $this->assertEqualsCanonicalizing(
            [$inClass->id, $lost->id],
            array_column($this->getJson('/api/interventions')->json('data'), 'id')
        );

        // The student leaves the teacher's class: the record is no longer listed (and could not be opened either).
        Enrollment::where('student_id', $gone->id)->where('class_assignment_id', $this->class->id)->delete();
        $this->assertSame([$inClass->id], array_column($this->getJson('/api/interventions')->json('data'), 'id'));

        // An archived class hides its records from everyone.
        $this->class->update(['status' => 'archived']);
        $this->class->delete();
        $this->getJson('/api/interventions')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_list_query_count_does_not_grow_with_records(): void
    {
        Sanctum::actingAs($this->owner);

        // The SQL of one request, with "?, ?, ?" lists collapsed so a longer id list is not a difference.
        $queries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/interventions')->assertOk();
            $sql = array_map(fn ($q) => preg_replace('/\?(,\s*\?)+/', '?', $q['query']), DB::getQueryLog());
            DB::disableQueryLog();

            return $sql;
        };

        foreach ([null, $this->class->id] as $classId) {
            $this->make(['class_assignment_id' => $classId]);
        }
        $this->getJson('/api/interventions')->assertOk(); // warm-up
        $few = $queries();

        for ($k = 0; $k < 8; $k++) {
            $this->make(['class_assignment_id' => $k % 2 ? $this->class->id : null]);
        }

        $this->assertSame($few, $queries());
    }

    // ================================================================ helpers

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function student(?ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'A-' . (++$this->seq), 'first_name' => 'Student' . $this->seq, 'last_name' => 'Test',
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

    private function payload(array $over = []): array
    {
        return array_merge([
            'concern_type' => 'missing_work', 'concern' => 'Repeated missing assignments',
            'intervention_type' => 'consultation', 'intervention_date' => '2030-03-01',
            'follow_up_date' => '2030-03-17', 'notes' => null,
        ], $over);
    }

    /** Creates an intervention for student1 as the owner, straight through the service. */
    private function make(array $over = []): Intervention
    {
        return app(InterventionService::class)->create($this->owner, $this->student1, $this->payload($over));
    }

    private function entry(string $to, array $over = []): array
    {
        return array_merge([
            'followup_date' => '2030-03-10', 'to_status' => $to, 'progress' => 'Talked it through',
            'notes' => 'Reason given', 'next_follow_up_date' => '2030-03-17', 'outcome' => 'improved',
        ], $over);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Intervention;
use App\Models\InterventionFollowup;
use App\Models\ParentCommunication;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\InterventionService;
use App\Services\ParentCommunicationService;
use App\Services\StudentNoteService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Notes, interventions, follow-ups and parent communications: service and policy rules.
 * These tests call the services and policies directly (the HTTP routes come in the API step).
 * "Today" is fixed at 2030-03-10 (school timezone). RefreshDatabase wipes the database, so this
 * refuses to run unless it is a *_test MySQL database (no :memory: here).
 */
class InterventionModuleTest extends TestCase
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

        // After the fixtures: "now" is 10 March 2030, mid-morning in Manila.
        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ================================================================ status transitions

    public static function allowedTransitions(): array
    {
        return [
            'planned to in progress' => ['planned', 'in_progress'],
            'planned to completed' => ['planned', 'completed'],
            'in progress to requires follow-up' => ['in_progress', 'requires_follow_up'],
            'in progress to completed' => ['in_progress', 'completed'],
            'requires follow-up to in progress' => ['requires_follow_up', 'in_progress'],
            'requires follow-up to completed' => ['requires_follow_up', 'completed'],
            'completed to in progress (reopen)' => ['completed', 'in_progress'],
            'in progress stays in progress' => ['in_progress', 'in_progress'],
            'requires follow-up stays' => ['requires_follow_up', 'requires_follow_up'],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions(string $from, string $to): void
    {
        $i = $this->reach($this->make($this->owner), $from);
        $history = InterventionFollowup::count();
        $audits = AuditLog::count();

        $this->svc()->addEntry($this->owner, $i, $this->valid($to));

        $i->refresh();
        $this->assertSame($to, $i->status);
        $this->assertSame($to === 'completed' ? null : '2030-03-17', $i->follow_up_date?->format('Y-m-d'));
        $this->assertSame($history + 1, InterventionFollowup::count());
        $this->assertSame($audits + 1, AuditLog::count());
    }

    public static function prohibitedTransitions(): array
    {
        return [
            'planned to requires follow-up' => ['planned', 'requires_follow_up'],
            'planned stays planned' => ['planned', 'planned'],
            'in progress back to planned' => ['in_progress', 'planned'],
            'requires follow-up back to planned' => ['requires_follow_up', 'planned'],
            'completed back to planned' => ['completed', 'planned'],
            'completed to requires follow-up' => ['completed', 'requires_follow_up'],
            'completed stays completed' => ['completed', 'completed'],
        ];
    }

    #[DataProvider('prohibitedTransitions')]
    public function test_prohibited_transitions_change_nothing(string $from, string $to): void
    {
        $i = $this->reach($this->make($this->owner), $from);

        $this->assertRejected($i, $this->valid($to), ['to_status']);
    }

    public function test_requires_follow_up_needs_a_reason_and_a_next_date(): void
    {
        $i = $this->reach($this->make($this->owner), 'in_progress');

        $this->assertRejected(
            $i,
            $this->valid('requires_follow_up', ['notes' => null, 'next_follow_up_date' => null]),
            ['notes', 'next_follow_up_date']
        );
    }

    public function test_completing_needs_an_outcome_and_clears_the_due_date(): void
    {
        $i = $this->reach($this->make($this->owner), 'requires_follow_up');
        $this->assertNotNull($i->follow_up_date);

        $this->assertRejected($i, $this->valid('completed', ['outcome' => null]), ['outcome']);

        $this->svc()->addEntry($this->owner, $i, $this->valid('completed', ['outcome' => 'partly_improved']));
        $i->refresh();

        $this->assertNull($i->follow_up_date);
        $this->assertSame('partly_improved', $i->outcome);
        // The history keeps what happened; completing does not delete earlier entries.
        $this->assertGreaterThanOrEqual(3, $i->followups()->count());
        $this->assertSame('completed', $i->followups()->get()->last()->entry_type);
    }

    public function test_reopening_needs_a_reason_is_audited_and_cycles_are_kept(): void
    {
        $i = $this->make($this->owner);

        $this->svc()->addEntry($this->owner, $i, $this->valid('completed'));
        $this->assertRejected($i->refresh(), $this->valid('in_progress', ['notes' => '  ']), ['notes']);

        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));
        $this->assertNull($i->refresh()->outcome);

        $reopened = AuditLog::where('auditable_type', Intervention::class)->where('event', 'reopened')->first();
        $this->assertNotNull($reopened);
        $this->assertSame('improved', $this->vals($reopened->old_values)['outcome']); // the earlier outcome is kept here

        $this->svc()->addEntry($this->owner, $i, $this->valid('completed'));
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));

        $this->assertSame(
            ['completed', 'reopened', 'completed', 'reopened'],
            $i->followups()->pluck('entry_type')->all()
        );
    }

    public function test_repeated_followups_and_rescheduling_are_separate_history(): void
    {
        $i = $this->make($this->owner);

        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress', ['next_follow_up_date' => '2030-03-18']));
        $this->svc()->addEntry($this->owner, $i, [
            'kind' => 'reschedule', 'followup_date' => '2030-03-10', 'next_follow_up_date' => '2030-03-25',
        ]);

        $this->assertSame(['followup', 'followup', 'rescheduled'], $i->followups()->pluck('entry_type')->all());
        $this->assertSame('2030-03-25', $i->refresh()->follow_up_date->format('Y-m-d'));
        $this->assertSame('in_progress', $i->status); // a reschedule never changes the status

        $this->assertRejected($i, ['kind' => 'reschedule', 'followup_date' => '2030-03-10'], ['next_follow_up_date']);

        $done = $this->make($this->owner);
        $this->svc()->addEntry($this->owner, $done, $this->valid('completed'));
        $this->assertRejected($done->refresh(), [
            'kind' => 'reschedule', 'followup_date' => '2030-03-10', 'next_follow_up_date' => '2030-03-20',
        ], ['to_status']);
    }

    public function test_entry_dates_are_validated(): void
    {
        $i = $this->make($this->owner, null, ['intervention_date' => '2030-03-01']);

        $this->assertRejected($i, $this->valid('in_progress', ['followup_date' => '2030-03-11']), ['followup_date']);  // future
        $this->assertRejected($i, $this->valid('in_progress', ['followup_date' => '2030-02-28']), ['followup_date']);  // before the intervention
        $this->assertRejected($i, $this->valid('in_progress', ['next_follow_up_date' => '2030-03-10']), ['next_follow_up_date']); // not after
    }

    public function test_a_stale_request_is_rejected(): void
    {
        $i = $this->make($this->owner);
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));

        // A second tab still thinks the intervention is Planned.
        $this->assertRejected($i, $this->valid('completed', ['expected_status' => 'planned']), ['status']);
    }

    public function test_a_failed_history_write_rolls_everything_back(): void
    {
        $i = $this->make($this->owner);
        $history = InterventionFollowup::count();

        // Make the audit write (the last step in the transaction) fail.
        AuditLog::creating(fn () => throw new RuntimeException('boom'));

        try {
            $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));
            $this->fail('Expected the failure to surface.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        } finally {
            AuditLog::flushEventListeners();
        }

        $this->assertSame('planned', $i->fresh()->status);
        $this->assertSame($history, InterventionFollowup::count());
    }

    // ================================================================ due follow-ups

    public function test_follow_up_is_due_at_midnight_manila_time_and_not_before(): void
    {
        $this->make($this->owner, null, ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-11']);

        $this->travelTo(Carbon::parse('2030-03-10 23:59:00', self::TZ));
        $this->assertCount(0, $this->svc()->due($this->owner));

        $this->travelTo(Carbon::parse('2030-03-11 00:00:00', self::TZ));
        $this->assertCount(1, $this->svc()->due($this->owner));

        // It stays due until it is completed.
        $this->travelTo(Carbon::parse('2030-03-20 08:00:00', self::TZ));
        $this->assertCount(1, $this->svc()->due($this->owner));
    }

    public function test_due_leaves_out_completed_deleted_other_authors_and_archived_classes(): void
    {
        $this->make($this->owner, null, ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05']);
        $done = $this->make($this->owner, null, ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05']);
        $gone = $this->make($this->owner, null, ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05']);
        $this->make($this->owner, null, ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05', 'class_assignment_id' => $this->class->id]);

        $this->assertCount(4, $this->svc()->due($this->owner));

        $this->svc()->addEntry($this->owner, $done, $this->valid('completed'));
        $this->svc()->delete($this->owner, $gone);
        $this->assertCount(2, $this->svc()->due($this->owner));

        $this->assertCount(0, $this->svc()->due($this->other));   // someone else's records
        $this->assertCount(0, $this->svc()->due($this->admin));   // admins have no records of their own

        $this->archive($this->class);
        $this->assertCount(0, $this->svc()->due($this->owner));   // the owner has no active class left, so the student is no longer theirs
    }

    public function test_due_query_count_does_not_grow_with_records(): void
    {
        $measure = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->svc()->due($this->owner);
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        for ($k = 0; $k < 2; $k++) {
            $this->make($this->owner, $this->student($this->class), ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05']);
        }
        $few = $measure();

        for ($k = 0; $k < 8; $k++) {
            $this->make($this->owner, $this->student($this->class), ['intervention_date' => '2030-03-01', 'follow_up_date' => '2030-03-05']);
        }

        $this->assertSame($few, $measure());
    }

    // ================================================================ privacy and ownership

    public function test_only_the_author_and_admins_can_use_a_record(): void
    {
        $i = $this->make($this->owner);
        $n = $this->note($this->owner);
        $c = $this->communication($this->owner);

        foreach ([$i, $n, $c] as $record) {
            foreach (['view', 'update', 'delete', 'restore'] as $ability) {
                $this->assertTrue($this->owner->can($ability, $record), "author $ability");
                $this->assertTrue($this->admin->can($ability, $record), "admin $ability");
                // $this->other also teaches this student, but did not write the record.
                $this->assertFalse($this->other->can($ability, $record), "other teacher $ability");
                $this->assertFalse($this->outsider->can($ability, $record), "outsider $ability");
            }
        }

        $this->assertTrue($this->owner->can('addEntry', $i));
        $this->assertFalse($this->other->can('addEntry', $i));
        $this->assertFalse($this->outsider->can('addEntry', $i));
    }

    public function test_other_teachers_lists_never_contain_the_record(): void
    {
        $this->make($this->owner);
        $this->note($this->owner);
        $this->communication($this->owner);

        $this->assertCount(0, $this->svc()->listForStudent($this->other, $this->student1));
        $this->assertCount(0, app(StudentNoteService::class)->listForStudent($this->other, $this->student1));
        $this->assertCount(0, app(ParentCommunicationService::class)->listForStudent($this->other, $this->student1));

        $this->assertCount(1, $this->svc()->listForStudent($this->owner, $this->student1));
    }

    public function test_the_author_needs_the_student_to_stay_visible(): void
    {
        $i = $this->make($this->owner);
        $this->assertTrue($this->owner->can('view', $i));

        Enrollment::where('student_id', $this->student1->id)->where('class_assignment_id', $this->class->id)->delete();

        $this->assertFalse($this->owner->can('view', $i->fresh()));
    }

    public function test_a_teacher_cannot_attach_records_to_students_or_classes_that_are_not_theirs(): void
    {
        $stranger = $this->student(null); // enrolled nowhere

        $this->expectException(AuthorizationException::class);
        $this->svc()->create($this->owner, $stranger, $this->base());
    }

    public function test_class_checks_on_create(): void
    {
        // Another teacher's class: one message for "not yours", so ids cannot be probed.
        $this->assertValidationFails(fn () => $this->svc()->create($this->owner, $this->student1, $this->base(['class_assignment_id' => $this->classOther->id])), 'class_assignment_id');

        // A student who is not on the class roster (admin may use any active class).
        $onlyOther = $this->student($this->classOther);
        $this->assertValidationFails(fn () => $this->svc()->create($this->admin, $onlyOther, $this->base(['class_assignment_id' => $this->class->id])), 'class_assignment_id');

        // The owner's own class and a student on its roster works.
        $i = $this->svc()->create($this->owner, $this->student1, $this->base(['class_assignment_id' => $this->class->id]));
        $this->assertSame($this->class->id, $i->class_assignment_id);
    }

    public function test_the_client_cannot_forge_owner_status_or_outcome(): void
    {
        $i = $this->svc()->create($this->owner, $this->student1, $this->base([
            'author_id' => $this->other->id, 'status' => 'completed', 'outcome' => 'improved',
            'student_id' => 999999, 'follow_up_date' => '2030-03-17',
        ]));

        $this->assertSame($this->owner->id, $i->author_id);
        $this->assertSame($this->student1->id, $i->student_id);
        $this->assertSame('planned', $i->status);
        $this->assertNull($i->outcome);

        $this->svc()->update($this->owner, $i, $this->base(['status' => 'completed', 'author_id' => $this->other->id, 'outcome' => 'improved']));
        $i->refresh();
        $this->assertSame('planned', $i->status);
        $this->assertSame($this->owner->id, $i->author_id);
        $this->assertNull($i->outcome);
    }

    // ================================================================ archived classes

    public function test_records_of_an_archived_class_are_hidden_from_everyone_including_admins(): void
    {
        $i = $this->make($this->owner, $this->student1, ['class_assignment_id' => $this->class->id, 'follow_up_date' => '2030-03-08']);
        $general = $this->make($this->owner); // no class: unaffected

        $this->assertTrue($this->owner->can('view', $i));
        $this->assertTrue($this->admin->can('view', $i));

        $this->archive($this->class);
        $i = $i->fresh();

        $this->assertFalse($this->owner->can('view', $i));
        $this->assertFalse($this->admin->can('update', $i));
        $this->assertFalse($this->admin->can('addEntry', $i));

        $ids = $this->svc()->listForStudent($this->admin, $this->student1)->pluck('id')->all();
        $this->assertSame([$general->id], $ids);
        $this->assertNotContains($i->id, $this->svc()->due($this->owner)->pluck('id')->all());

        // New records cannot be added to an archived class, not even by an admin.
        $this->assertValidationFails(fn () => $this->svc()->create($this->admin, $this->student1, $this->base(['class_assignment_id' => $this->class->id])), 'class_assignment_id');

        // Nothing was deleted: restoring the class brings the records back.
        $this->class->restore();
        $this->class->update(['status' => 'active']);
        $this->assertTrue($this->owner->can('view', $i->fresh()));
        $this->assertContains($i->id, $this->svc()->listForStudent($this->owner, $this->student1)->pluck('id')->all());
    }

    // ================================================================ audit

    public function test_admin_reads_are_audited_once_per_request_and_teacher_reads_are_not(): void
    {
        $a = $this->make($this->owner);
        $b = $this->make($this->owner);

        $this->svc()->listForStudent($this->owner, $this->student1);
        $this->assertSame(0, AuditLog::where('event', 'admin_read')->count());

        $this->svc()->listForStudent($this->admin, $this->student1);
        $rows = AuditLog::where('event', 'admin_read')->get();
        $this->assertCount(1, $rows);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->vals($rows->first()->new_values)['record_ids']);
        $this->assertSame('intervention', $this->vals($rows->first()->new_values)['record_type']);

        // Nothing to read, nothing logged.
        $this->svc()->listForStudent($this->admin, $this->student($this->class));
        $this->assertCount(1, AuditLog::where('event', 'admin_read')->get());
    }

    public function test_an_admin_edit_of_someone_elses_record_is_a_correction(): void
    {
        $i = $this->make($this->owner);

        $this->svc()->update($this->owner, $i, $this->base(['concern_type' => 'other']));
        $this->assertSame(1, AuditLog::where('event', 'updated')->count());

        $this->svc()->update($this->admin, $i, $this->base(['concern_type' => 'attendance']));
        $this->assertSame(1, AuditLog::where('event', 'admin_corrected')->count());
    }

    public function test_no_free_text_reaches_the_audit_log(): void
    {
        $secrets = ['SECRET-CONCERN-7731', 'SECRET-NOTES-4410', 'SECRET-PROGRESS-9082', 'SECRET-REASON-1156', 'SECRET-BODY-3307', 'SECRET-SUMMARY-6620', 'SECRET-WHY-2284'];

        $i = $this->make($this->owner, null, ['concern' => $secrets[0], 'notes' => $secrets[1]]);
        $this->svc()->update($this->owner, $i, $this->base(['concern' => $secrets[0] . ' changed', 'notes' => $secrets[1] . ' changed']));
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress', ['progress' => $secrets[2], 'notes' => $secrets[3]]));
        $this->svc()->addEntry($this->owner, $i, $this->valid('completed'));
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress', ['notes' => $secrets[3]]));

        $note = $this->note($this->owner, $secrets[4]);
        app(StudentNoteService::class)->update($this->owner, $note, ['body' => $secrets[4] . ' edited']);
        $comm = $this->communication($this->owner, ['reason' => $secrets[6], 'summary' => $secrets[5]]);
        app(ParentCommunicationService::class)->update($this->admin, $comm, $this->commData(['reason' => $secrets[6] . ' x', 'summary' => $secrets[5] . ' x']));

        $this->svc()->listForStudent($this->admin, $this->student1);
        $this->svc()->delete($this->owner, $i);

        $everything = AuditLog::all()->map(fn ($r) => json_encode([$r->old_values, $r->new_values]))->implode(' ');
        $this->assertNotSame('', $everything);
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $everything);
        }
        // The flags that say "text changed" are there instead.
        $this->assertStringContainsString('concern_changed', $everything);
        $this->assertStringContainsString('body_changed', $everything);
        $this->assertStringContainsString('summary_changed', $everything);
    }

    public function test_delete_and_restore_are_audited_and_hide_the_record(): void
    {
        $i = $this->make($this->owner, null, ['follow_up_date' => '2030-03-08', 'intervention_date' => '2030-03-01']);

        $this->svc()->delete($this->owner, $i);
        $this->assertCount(0, $this->svc()->listForStudent($this->owner, $this->student1));
        $this->assertCount(0, $this->svc()->due($this->owner));
        $this->assertValidationFails(fn () => $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress')), 'intervention');

        $this->svc()->restore($this->owner, $i->fresh() ?? Intervention::withTrashed()->find($i->id));
        $this->assertCount(1, $this->svc()->listForStudent($this->owner, $this->student1));
        $this->assertSame(1, AuditLog::where('event', 'deleted')->count());
        $this->assertSame(1, AuditLog::where('event', 'restored')->count());
    }

    // ================================================================ notes and parent communications

    public function test_notes_and_communications_are_hidden_when_their_class_is_archived(): void
    {
        $n = $this->note($this->owner, 'a note', $this->class->id);
        $c = $this->communication($this->owner, ['class_assignment_id' => $this->class->id]);
        $this->assertTrue($this->admin->can('view', $n));

        $this->archive($this->class);

        $this->assertFalse($this->admin->can('view', $n->fresh()));
        $this->assertFalse($this->admin->can('view', $c->fresh()));
        $this->assertCount(0, app(StudentNoteService::class)->listForStudent($this->admin, $this->student1));
        $this->assertCount(0, app(ParentCommunicationService::class)->listForStudent($this->admin, $this->student1));
    }

    public function test_parent_communication_validation(): void
    {
        $svc = app(ParentCommunicationService::class);

        $this->assertValidationFails(fn () => $svc->create($this->owner, $this->student1, $this->commData(['contact_date' => '2030-03-11'])), 'contact_date');
        $this->assertValidationFails(fn () => $svc->create($this->owner, $this->student1, $this->commData(['method' => 'carrier_pigeon'])), 'method');
        $this->assertValidationFails(fn () => $svc->create($this->owner, $this->student1, $this->commData(['contact_date' => '2030-03-10', 'follow_up_date' => '2030-03-09'])), 'follow_up_date');

        $c = $svc->create($this->owner, $this->student1, $this->commData(['follow_up_date' => '2030-03-17']));
        $this->assertSame($this->owner->id, $c->author_id);
        $this->assertSame(1, ParentCommunication::count()); // the three invalid attempts saved nothing
    }

    public function test_the_outcome_stays_in_the_history_after_a_reopen(): void
    {
        $i = $this->make($this->owner);

        $this->svc()->addEntry($this->owner, $i, $this->valid('completed', ['outcome' => 'partly_improved']));
        $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));
        $this->svc()->addEntry($this->owner, $i, $this->valid('completed', ['outcome' => 'improved']));

        $this->assertSame('improved', $i->refresh()->outcome);
        $this->assertSame(['partly_improved', null, 'improved'], $i->followups()->pluck('outcome')->all());
    }
    // ================================================================ helpers

    private function svc(): InterventionService
    {
        return app(InterventionService::class);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function student(?ClassAssignment $class): Student
    {
        $student = Student::create([
            'student_id' => 'I-' . (++$this->seq), 'first_name' => 'Student' . $this->seq, 'last_name' => 'Test',
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

    /** Archiving soft-deletes the class and sets its status. */
    private function archive(ClassAssignment $class): void
    {
        $class->update(['status' => 'archived']);
        $class->delete();
    }

    private function base(array $over = []): array
    {
        return array_merge([
            'concern_type' => 'missing_work', 'concern' => 'Repeated missing assignments',
            'intervention_type' => 'consultation', 'intervention_date' => '2030-03-01',
            'follow_up_date' => '2030-03-17', 'notes' => null,
        ], $over);
    }

    private function make(User $by, ?Student $student = null, array $over = []): Intervention
    {
        return $this->svc()->create($by, $student ?? $this->student1, $this->base($over));
    }

    /** A valid entry for the target status; tests blank out the field they want to break. */
    private function valid(string $to, array $over = []): array
    {
        return array_merge([
            'followup_date' => '2030-03-10', 'to_status' => $to, 'progress' => 'Talked it through',
            'notes' => 'Reason given', 'next_follow_up_date' => '2030-03-17', 'outcome' => 'improved',
        ], $over);
    }

    /** Walks a new (Planned) intervention to the wanted status through the service. */
    private function reach(Intervention $i, string $state): Intervention
    {
        if (in_array($state, ['in_progress', 'requires_follow_up'], true)) {
            $this->svc()->addEntry($this->owner, $i, $this->valid('in_progress'));
        }
        if ($state === 'requires_follow_up') {
            $this->svc()->addEntry($this->owner, $i, $this->valid('requires_follow_up'));
        }
        if ($state === 'completed') {
            $this->svc()->addEntry($this->owner, $i, $this->valid('completed'));
        }

        return $i->refresh();
    }

    /** The entry must fail validation on these keys and change nothing at all. */
    private function assertRejected(Intervention $i, array $payload, array $keys): void
    {
        $before = $i->fresh();
        $history = InterventionFollowup::count();
        $audits = AuditLog::count();

        try {
            $this->svc()->addEntry($this->owner, $i, $payload);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $e->errors());
            }
        }

        $after = $i->fresh();
        $this->assertSame($before->status, $after->status);
        $this->assertSame($before->follow_up_date?->format('Y-m-d'), $after->follow_up_date?->format('Y-m-d'));
        $this->assertSame($before->outcome, $after->outcome);
        $this->assertSame($history, InterventionFollowup::count());
        $this->assertSame($audits, AuditLog::count());
    }

    private function assertValidationFails(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    private function note(User $by, string $body = 'A private note', ?int $classId = null): StudentNote
    {
        return app(StudentNoteService::class)->create($by, $this->student1, ['body' => $body, 'class_assignment_id' => $classId]);
    }

    private function commData(array $over = []): array
    {
        return array_merge([
            'contact_date' => '2030-03-10', 'method' => 'phone_call', 'reason' => 'Missing work', 'summary' => 'Spoke with the guardian',
        ], $over);
    }

    private function communication(User $by, array $over = []): ParentCommunication
    {
        return app(ParentCommunicationService::class)->create($by, $this->student1, $this->commData($over));
    }

    /** Audit values may come back as an array or a JSON string depending on the model casts. */
    private function vals(mixed $v): array
    {
        return is_string($v) ? (json_decode($v, true) ?? []) : (array) $v;
    }
}

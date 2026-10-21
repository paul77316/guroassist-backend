<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Competency;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSet;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CompetencyService;
use App\Services\ExamQuestionService;
use App\Services\ExamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

/**
 * Exams, sets, questions and competencies: service and policy rules.
 * These tests call the services and policies directly (the HTTP routes are in ExamApiTest).
 * "Today" is fixed at 2030-03-10 (school timezone). RefreshDatabase wipes the database, so this
 * refuses to run unless it is a *_test MySQL database.
 */
class ExamModuleTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;      // teacher who owns $class
    private User $other;      // teacher who owns $classOther
    private User $admin;
    private ClassAssignment $class;
    private ClassAssignment $classOther;
    private Competency $comp;
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

        $this->owner = User::factory()->create(['role' => 'teacher', 'email' => 'owner@test.local']);
        $this->other = User::factory()->create(['role' => 'teacher', 'email' => 'other@test.local']);
        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@test.local']);
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
        $this->classOther = ClassAssignment::create([
            'teacher_id' => $otherProfile->id, 'subject_id' => $science->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'status' => 'active',
        ]);

        $this->comp = $this->competency($this->owner, 'Solves linear equations');

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', self::TZ));
    }

    // ---------------------------------------------------------------- helpers

    private function competency(User $owner, string $name): Competency
    {
        $c = new Competency(['name' => $name]);
        $c->owner_id = $owner->id;
        $c->save();

        return $c;
    }

    private function exam(?User $author = null, ?ClassAssignment $class = null, int $choices = 4): Exam
    {
        return app(ExamService::class)->create($author ?? $this->owner, [
            'class_assignment_id' => ($class ?? $this->class)->id,
            'title' => 'Quarter 1 Quiz',
            'exam_date' => '2030-03-20',
            'choice_count' => $choices,
        ]);
    }

    private function payload(array $over = []): array
    {
        $this->seq++;

        return array_merge([
            'body' => 'Question text number ' . $this->seq,
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'B',
            'competency_id' => $this->comp->id,
        ], $over);
    }

    private function question(ExamSet $set, ?User $by = null, array $over = []): ExamQuestion
    {
        return app(ExamQuestionService::class)->create($by ?? $this->owner, $set, $this->payload($over));
    }

    private function assertInvalid(callable $fn, string $key): void
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());

            return;
        }
        $this->fail("Expected a validation error on '$key'.");
    }

    // ================================================================ exams and sets

    public function test_creating_an_exam_makes_set_a_with_an_opaque_token(): void
    {
        $exam = $this->exam();

        $this->assertEquals($this->owner->id, $exam->author_id);
        $this->assertEquals($this->class->id, $exam->class_assignment_id);
        $this->assertEquals(['A'], $exam->sets()->pluck('label')->all());

        $token = $exam->sets()->first()->qr_token;
        $this->assertEquals(40, strlen($token));
        $this->assertEquals(1, AuditLog::where('event', 'created')->count());
    }

    public function test_a_teacher_cannot_create_an_exam_for_a_class_that_is_not_theirs_or_archived(): void
    {
        $svc = app(ExamService::class);
        $data = ['title' => 'T', 'exam_date' => '2030-03-20', 'choice_count' => 4];

        $this->assertInvalid(fn () => $svc->create($this->owner, $data + ['class_assignment_id' => $this->classOther->id]), 'class_assignment_id');
        $this->assertInvalid(fn () => $svc->create($this->owner, $data + ['class_assignment_id' => 999999]), 'class_assignment_id');

        $this->class->delete(); // archived
        $this->assertInvalid(fn () => $svc->create($this->owner, $data + ['class_assignment_id' => $this->class->id]), 'class_assignment_id');
        $this->assertEquals(0, Exam::count());
    }

    public function test_the_choice_count_is_locked_once_the_exam_has_questions(): void
    {
        $exam = $this->exam();
        $svc = app(ExamService::class);

        // Allowed while empty.
        $svc->update($this->owner, $exam, ['title' => 'Quiz', 'exam_date' => '2030-03-20', 'choice_count' => 5]);
        $this->assertEquals(5, $exam->refresh()->choice_count);

        $this->question($exam->sets()->first(), null, ['choices' => ['a', 'b', 'c', 'd', 'e']]);

        $this->assertInvalid(
            fn () => $svc->update($this->owner, $exam, ['title' => 'Quiz', 'exam_date' => '2030-03-20', 'choice_count' => 4]),
            'choice_count'
        );
        $this->assertEquals(5, $exam->refresh()->choice_count);
    }

    public function test_set_rules(): void
    {
        $exam = $this->exam();
        $svc = app(ExamService::class);

        $second = $svc->addSet($this->owner, $exam);
        $third = $svc->addSet($this->owner, $exam);
        $this->assertEquals(['A', 'B', 'C'], $exam->sets()->orderBy('label')->pluck('label')->all());
        $this->assertNotSame($second->qr_token, $third->qr_token);

        // A freed label is reused.
        $svc->deleteSet($this->owner, $second);
        $this->assertEquals('B', $svc->addSet($this->owner, $exam)->label);

        // The limit.
        while ($exam->sets()->count() < ExamService::MAX_SETS) {
            $svc->addSet($this->owner, $exam);
        }
        $this->assertInvalid(fn () => $svc->addSet($this->owner, $exam), 'exam');

        // The last set cannot go.
        $solo = $this->exam();
        $this->assertInvalid(fn () => $svc->deleteSet($this->owner, $solo->sets()->first()), 'set');
    }

    public function test_deleting_a_set_removes_its_questions(): void
    {
        $exam = $this->exam();
        $svc = app(ExamService::class);
        $setB = $svc->addSet($this->owner, $exam);
        $this->question($exam->sets()->orderBy('label')->first());
        $this->question($setB);
        $this->question($setB);

        $svc->deleteSet($this->owner, $setB);

        $this->assertEquals(1, ExamQuestion::count());
    }

    // ================================================================ questions

    public function test_question_rules(): void
    {
        $exam = $this->exam(null, null, 4);
        $set = $exam->sets()->first();
        $svc = app(ExamQuestionService::class);

        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', 'c']])), 'choices');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', 'c', 'd', 'e']])), 'choices');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', ' ', 'd']])), 'choices');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['correct_choice' => 'E'])), 'correct_choice');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['body' => '   '])), 'body');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['competency_id' => 999999])), 'competency_id');

        // Someone else's competency cannot be used.
        $theirs = $this->competency($this->other, 'Their competency');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['competency_id' => $theirs->id])), 'competency_id');

        $this->assertEquals(0, ExamQuestion::count());

        // A lower-case correct choice is accepted and stored as a capital.
        $q = $svc->create($this->owner, $set, $this->payload(['correct_choice' => 'c']));
        $this->assertEquals('C', $q->correct_choice);
        $this->assertEquals(1, $q->position);
    }

    public function test_a_five_choice_exam_accepts_e(): void
    {
        $exam = $this->exam(null, null, 5);
        $q = $this->question($exam->sets()->first(), null, ['choices' => ['a', 'b', 'c', 'd', 'e'], 'correct_choice' => 'E']);

        $this->assertEquals('E', $q->correct_choice);
    }

    public function test_a_set_holds_at_most_fifty_questions(): void
    {
        $set = $this->exam()->sets()->first();

        for ($i = 0; $i < ExamQuestionService::MAX_QUESTIONS; $i++) {
            $this->question($set);
        }

        $this->assertInvalid(fn () => $this->question($set), 'body');
        $this->assertEquals(50, ExamQuestion::count());
    }

    public function test_positions_close_the_gap_after_a_delete(): void
    {
        $set = $this->exam()->sets()->first();
        $a = $this->question($set);
        $b = $this->question($set);
        $c = $this->question($set);

        app(ExamQuestionService::class)->delete($this->owner, $b);

        $this->assertEquals(1, $a->refresh()->position);
        $this->assertEquals(2, $c->refresh()->position);
    }

    public function test_reorder_needs_every_question_exactly_once(): void
    {
        $set = $this->exam()->sets()->first();
        $a = $this->question($set);
        $b = $this->question($set);
        $c = $this->question($set);
        $svc = app(ExamQuestionService::class);

        $svc->reorder($this->owner, $set, [$c->id, $a->id, $b->id]);
        $this->assertEquals([$c->id, $a->id, $b->id], $set->questions()->pluck('id')->all());

        $this->assertInvalid(fn () => $svc->reorder($this->owner, $set, [$a->id, $b->id]), 'ids');
        $this->assertInvalid(fn () => $svc->reorder($this->owner, $set, [$a->id, $a->id, $b->id]), 'ids');
        $this->assertInvalid(fn () => $svc->reorder($this->owner, $set, [$a->id, $b->id, 999999]), 'ids');

        // A question of another exam cannot be slipped in.
        $foreign = $this->question($this->exam()->sets()->first());
        $this->assertInvalid(fn () => $svc->reorder($this->owner, $set, [$a->id, $b->id, $foreign->id]), 'ids');
        $this->assertEquals([$c->id, $a->id, $b->id], $set->questions()->pluck('id')->all());
    }

    // ================================================================ competencies

    public function test_competency_rules(): void
    {
        $svc = app(CompetencyService::class);

        // Same name (any case) for the same owner and subject is refused.
        $this->assertInvalid(fn () => $svc->create($this->owner, ['name' => 'SOLVES LINEAR EQUATIONS']), 'name');
        // Another teacher may use the same name.
        $this->assertEquals('Solves linear equations', $svc->create($this->other, ['name' => 'Solves linear equations'])->name);
        // The same name under a subject is a different entry.
        $subject = Subject::first();
        $this->assertNotNull($svc->create($this->owner, ['name' => 'Solves linear equations', 'subject_id' => $subject->id])->id);

        // Used by a question: cannot be deleted.
        $this->question($this->exam()->sets()->first());
        $this->assertInvalid(fn () => $svc->delete($this->owner, $this->comp), 'competency');
        $this->assertNotNull(Competency::find($this->comp->id));

        // Unused: can be deleted.
        $spare = $svc->create($this->owner, ['name' => 'Reads a graph']);
        $svc->delete($this->owner, $spare);
        $this->assertNull(Competency::find($spare->id));
    }

    // ================================================================ policies

    public function test_exam_policy(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $q = $this->question($set);

        foreach (['view', 'update', 'delete', 'restore'] as $ability) {
            $this->assertTrue($this->owner->can($ability, $exam), "owner $ability");
            $this->assertTrue($this->admin->can($ability, $exam), "admin $ability");
            $this->assertFalse($this->other->can($ability, $exam), "other $ability");
        }

        $this->assertTrue($this->owner->can('update', $set));
        $this->assertTrue($this->admin->can('view', $set));
        $this->assertFalse($this->other->can('view', $set));
        $this->assertTrue($this->owner->can('update', $q));
        $this->assertFalse($this->other->can('view', $q));

        // Only teachers create exams.
        $this->assertTrue($this->owner->can('create', Exam::class));
        $this->assertFalse($this->admin->can('create', Exam::class));
    }

    public function test_an_archived_class_hides_the_exam_from_everyone_including_admins(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $q = $this->question($set);

        $this->class->delete(); // archive

        foreach ([$this->owner, $this->admin] as $user) {
            $this->assertFalse($user->can('view', $exam->refresh()));
            $this->assertFalse($user->can('view', $set));
            $this->assertFalse($user->can('view', $q));
            $this->assertCount(0, app(ExamService::class)->list($user));
        }

        // Nothing was deleted: the exam returns when the class is restored.
        $this->class->restore();
        $this->assertTrue($this->owner->can('view', $exam->refresh()));
        $this->assertCount(1, app(ExamService::class)->list($this->owner));
    }

    public function test_a_deleted_exam_is_not_reachable_through_its_sets_and_comes_back(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $svc = app(ExamService::class);

        $svc->delete($this->owner, $exam);
        $this->assertFalse($this->owner->can('view', $set->refresh()));
        $this->assertCount(0, $svc->list($this->owner));
        $this->assertCount(1, $svc->list($this->owner, null, true));

        $svc->restore($this->owner, Exam::withTrashed()->find($exam->id));
        $this->assertTrue($this->owner->can('view', $set->refresh()));
    }

    public function test_lists_never_contain_another_authors_exams_for_a_teacher(): void
    {
        $mine = $this->exam();
        $theirs = $this->exam($this->other, $this->classOther);
        $svc = app(ExamService::class);

        $this->assertEquals([$mine->id], $svc->list($this->owner)->pluck('id')->all());
        $this->assertEquals([$theirs->id], $svc->list($this->other)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$mine->id, $theirs->id], $svc->list($this->admin)->pluck('id')->all());
    }

    // ================================================================ audit

    public function test_admin_reads_are_audited_once_per_request_and_teacher_reads_are_not(): void
    {
        $this->exam();
        $this->exam($this->other, $this->classOther);
        $svc = app(ExamService::class);

        $svc->list($this->owner);
        $this->assertEquals(0, AuditLog::where('event', 'admin_read')->count());

        $svc->list($this->admin);
        $this->assertEquals(1, AuditLog::where('event', 'admin_read')->count());
    }

    public function test_an_admin_edit_of_someone_elses_exam_is_a_correction(): void
    {
        $exam = $this->exam();
        $svc = app(ExamService::class);

        $svc->update($this->owner, $exam, ['title' => 'Mine', 'exam_date' => '2030-03-21', 'choice_count' => 4]);
        $svc->update($this->admin, $exam, ['title' => 'Fixed', 'exam_date' => '2030-03-21', 'choice_count' => 4]);

        $this->assertEquals(1, AuditLog::where('event', 'updated')->count());
        $this->assertEquals(1, AuditLog::where('event', 'admin_corrected')->count());
    }

    public function test_no_free_text_reaches_the_audit_log(): void
    {
        $exam = app(ExamService::class)->create($this->owner, [
            'class_assignment_id' => $this->class->id,
            'title' => 'ZZ-SECRET-TITLE',
            'exam_date' => '2030-03-20',
            'choice_count' => 4,
        ]);
        $set = $exam->sets()->first();
        $q = app(ExamQuestionService::class)->create($this->owner, $set, $this->payload([
            'body' => 'ZZ-SECRET-QUESTION',
            'choices' => ['ZZ-SECRET-A', 'ZZ-SECRET-B', 'ZZ-SECRET-C', 'ZZ-SECRET-D'],
        ]));
        app(ExamQuestionService::class)->update($this->owner, $q, $this->payload([
            'body' => 'ZZ-SECRET-EDIT',
            'choices' => ['ZZ-SECRET-A', 'ZZ-SECRET-B', 'ZZ-SECRET-C', 'ZZ-SECRET-D'],
        ]));
        app(ExamService::class)->update($this->owner, $exam, ['title' => 'ZZ-SECRET-TITLE-2', 'exam_date' => '2030-03-20', 'choice_count' => 4]);
        app(CompetencyService::class)->create($this->owner, ['name' => 'ZZ-SECRET-COMPETENCY']);
        app(ExamQuestionService::class)->delete($this->owner, $q);

        $this->assertGreaterThan(5, AuditLog::count());
        $this->assertStringNotContainsString('ZZ-SECRET', json_encode(AuditLog::all()->toArray()));
    }

    public function test_the_audit_flags_what_changed_without_the_text(): void
    {
        $set = $this->exam()->sets()->first();
        $q = $this->question($set);

        app(ExamQuestionService::class)->update($this->owner, $q, $this->payload([
            'body' => 'A different question',
            'correct_choice' => 'D',
        ]));

        $row = AuditLog::where('event', 'question_updated')->first();
        $new = is_string($row->new_values) ? json_decode($row->new_values, true) : $row->new_values;

        $this->assertTrue($new['text_changed']);
        $this->assertTrue($new['correct_changed']);
        $this->assertFalse($new['choices_changed']);
    }

    public function test_a_three_choice_exam_uses_a_to_c(): void
    {
        $exam = $this->exam(null, null, 3);
        $set = $exam->sets()->first();
        $svc = app(ExamQuestionService::class);

        $q = $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', 'c'], 'correct_choice' => 'C']));
        $this->assertEquals('C', $q->correct_choice);

        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', 'c'], 'correct_choice' => 'D'])), 'correct_choice');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b', 'c', 'd']])), 'choices');
        $this->assertInvalid(fn () => $svc->create($this->owner, $set, $this->payload(['choices' => ['a', 'b']])), 'choices');
        $this->assertEquals(1, ExamQuestion::count());
    }
}
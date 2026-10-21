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
use App\Services\ExamQuestionService;
use App\Services\ExamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Exams, sets, questions, competencies and question images over HTTP.
 * "Today" is fixed at 2030-03-10 (school timezone). RefreshDatabase wipes the database, so this
 * refuses to run unless it is a *_test MySQL database.
 */
class ExamApiTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Manila';

    private User $owner;
    private User $other;
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
        Storage::fake('local');

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

    private function examPayload(array $over = []): array
    {
        return array_merge([
            'class_assignment_id' => $this->class->id,
            'title' => 'Quarter 1 Quiz',
            'exam_date' => '2030-03-20',
            'choice_count' => 4,
        ], $over);
    }

    private function questionPayload(array $over = []): array
    {
        $this->seq++;

        return array_merge([
            'body' => 'Question text number ' . $this->seq,
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'B',
            'competency_id' => $this->comp->id,
        ], $over);
    }

    private function question(ExamSet $set, array $over = []): ExamQuestion
    {
        return app(ExamQuestionService::class)->create($this->owner, $set, $this->questionPayload($over));
    }

    private function queries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    // ================================================================ access

    public function test_guests_are_rejected_everywhere(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $q = $this->question($set);

        $this->getJson('/api/exam-options')->assertUnauthorized();
        $this->getJson('/api/competencies')->assertUnauthorized();
        $this->getJson('/api/exams')->assertUnauthorized();
        $this->postJson('/api/exams', $this->examPayload())->assertUnauthorized();
        $this->getJson("/api/exams/{$exam->id}")->assertUnauthorized();
        $this->getJson("/api/exam-sets/{$set->id}")->assertUnauthorized();
        $this->postJson("/api/exam-sets/{$set->id}/questions", $this->questionPayload())->assertUnauthorized();
        $this->getJson("/api/exam-questions/{$q->id}/image")->assertUnauthorized();
    }

    public function test_options_list_the_limits(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/exam-options')
            ->assertOk()
            ->assertJsonPath('data.choice_counts', [3, 4, 5])
            ->assertJsonPath('data.max_questions', 50)
            ->assertJsonPath('data.max_sets', 10)
            ->assertJsonPath('data.image.max_kb', 2048);
    }

    public function test_only_the_author_and_admins_reach_an_exam(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $q = $this->question($set);

        Sanctum::actingAs($this->other);
        $this->getJson("/api/exams/{$exam->id}")->assertForbidden();
        $this->putJson("/api/exams/{$exam->id}", $this->examPayload())->assertForbidden();
        $this->deleteJson("/api/exams/{$exam->id}")->assertForbidden();
        $this->postJson("/api/exams/{$exam->id}/sets")->assertForbidden();
        $this->getJson("/api/exam-sets/{$set->id}")->assertForbidden();
        $this->postJson("/api/exam-sets/{$set->id}/questions", $this->questionPayload())->assertForbidden();
        $this->putJson("/api/exam-questions/{$q->id}", $this->questionPayload())->assertForbidden();
        $this->deleteJson("/api/exam-questions/{$q->id}")->assertForbidden();
        $this->getJson('/api/exams')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->admin);
        $this->getJson("/api/exams/{$exam->id}")->assertOk();
        $this->getJson("/api/exam-sets/{$set->id}")->assertOk();

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/exams/{$exam->id}")->assertOk();
    }

    public function test_admins_cannot_create_exams_or_use_competencies(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/exams', $this->examPayload())->assertForbidden();
        $this->getJson('/api/competencies')->assertForbidden();
        $this->postJson('/api/competencies', ['name' => 'X'])->assertForbidden();
    }

    // ================================================================ exams

    public function test_a_teacher_creates_an_exam_and_cannot_forge_server_fields(): void
    {
        Sanctum::actingAs($this->owner);

        $res = $this->postJson('/api/exams', $this->examPayload([
            'author_id' => $this->other->id,
            'deleted_at' => '2030-01-01 00:00:00',
        ]))->assertCreated()
            ->assertJsonPath('data.title', 'Quarter 1 Quiz')
            ->assertJsonPath('data.exam_date', '2030-03-20')
            ->assertJsonPath('data.choice_count', 4)
            ->assertJsonPath('data.deleted', false)
            ->assertJsonCount(1, 'data.sets')
            ->assertJsonPath('data.sets.0.label', 'A');

        $exam = Exam::find($res->json('data.id'));
        $this->assertEquals($this->owner->id, $exam->author_id);
        $this->assertNull($exam->deleted_at);
        $this->assertEquals(40, strlen($res->json('data.sets.0.qr_token')));
    }

    public function test_creating_validates_the_payload(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/exams', [])->assertStatus(422)
            ->assertJsonValidationErrors(['class_assignment_id', 'title', 'exam_date', 'choice_count']);
        $this->postJson('/api/exams', $this->examPayload(['choice_count' => 6]))->assertStatus(422)->assertJsonValidationErrors('choice_count');
        $this->postJson('/api/exams', $this->examPayload(['choice_count' => 2]))->assertStatus(422)->assertJsonValidationErrors('choice_count');
        $this->postJson('/api/exams', $this->examPayload(['exam_date' => '20-03-2030']))->assertStatus(422)->assertJsonValidationErrors('exam_date');
        $this->postJson('/api/exams', $this->examPayload(['title' => str_repeat('x', 161)]))->assertStatus(422)->assertJsonValidationErrors('title');
        $this->assertEquals(0, Exam::count());
    }

    public function test_a_teacher_cannot_create_for_a_class_that_is_not_theirs_or_archived(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/exams', $this->examPayload(['class_assignment_id' => $this->classOther->id]))
            ->assertStatus(422)->assertJsonValidationErrors('class_assignment_id');

        $this->class->delete();
        $this->postJson('/api/exams', $this->examPayload())
            ->assertStatus(422)->assertJsonValidationErrors('class_assignment_id');
        $this->assertEquals(0, Exam::count());
    }

    public function test_lists_carry_counts_and_no_question_text_but_the_detail_does(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $this->question($set, ['body' => 'ZZ-LIST-SECRET-BODY']);
        $this->question($set);
        Sanctum::actingAs($this->owner);

        $list = $this->getJson('/api/exams')->assertOk()
            ->assertJsonPath('data.0.set_count', 1)
            ->assertJsonPath('data.0.question_count', 2)
            ->assertJsonPath('data.0.class.subject', 'Mathematics');
        $this->assertStringNotContainsString('ZZ-LIST-SECRET-BODY', $list->getContent());

        $detail = $this->getJson("/api/exams/{$exam->id}")->assertOk()
            ->assertJsonPath('data.sets.0.question_count', 2);
        $this->assertStringNotContainsString('ZZ-LIST-SECRET-BODY', $detail->getContent());

        $this->getJson("/api/exam-sets/{$set->id}")->assertOk()
            ->assertJsonPath('data.questions.0.body', 'ZZ-LIST-SECRET-BODY')
            ->assertJsonPath('data.questions.0.correct_choice', 'B')
            ->assertJsonPath('data.questions.0.competency.name', 'Solves linear equations')
            ->assertJsonPath('data.exam.choice_count', 4);
    }

    public function test_the_list_filters_by_class(): void
    {
        $mine = $this->exam();
        $this->exam($this->other, $this->classOther);
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/exams')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/exams?class_assignment_id=' . $this->class->id)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
        $this->getJson('/api/exams?trashed=yes')->assertStatus(422);
    }

    public function test_an_exam_can_be_edited_and_the_choice_count_locks_once_it_has_questions(): void
    {
        $exam = $this->exam();
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/exams/{$exam->id}", $this->examPayload(['title' => 'Renamed', 'choice_count' => 5]))
            ->assertOk()->assertJsonPath('data.title', 'Renamed')->assertJsonPath('data.choice_count', 5);

        $this->question($exam->sets()->first(), ['choices' => ['a', 'b', 'c', 'd', 'e']]);

        $this->putJson("/api/exams/{$exam->id}", $this->examPayload(['choice_count' => 4]))
            ->assertStatus(422)->assertJsonValidationErrors('choice_count');
    }

    public function test_delete_and_restore_over_http(): void
    {
        $exam = $this->exam();
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/exams/{$exam->id}/restore")->assertStatus(422);

        $this->deleteJson("/api/exams/{$exam->id}")->assertNoContent();
        $this->getJson("/api/exams/{$exam->id}")->assertNotFound();
        $this->getJson('/api/exams')->assertJsonCount(0, 'data');
        $this->getJson('/api/exams?trashed=only')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.deleted', true);

        $this->postJson("/api/exams/{$exam->id}/restore")->assertOk()->assertJsonPath('data.deleted', false);
        $this->getJson("/api/exams/{$exam->id}")->assertOk();

        $this->assertEquals(1, AuditLog::where('event', 'deleted')->count());
        $this->assertEquals(1, AuditLog::where('event', 'restored')->count());

        // Another teacher cannot restore it.
        $this->deleteJson("/api/exams/{$exam->id}")->assertNoContent();
        Sanctum::actingAs($this->other);
        $this->postJson("/api/exams/{$exam->id}/restore")->assertForbidden();
    }

    public function test_an_archived_class_hides_the_exam_from_everyone_and_it_returns_with_the_class(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $q = $this->question($set);
        $this->class->delete();

        foreach ([$this->owner, $this->admin] as $user) {
            Sanctum::actingAs($user);
            $this->getJson('/api/exams')->assertOk()->assertJsonCount(0, 'data');
            $this->getJson("/api/exams/{$exam->id}")->assertForbidden();
            $this->getJson("/api/exam-sets/{$set->id}")->assertForbidden();
            $this->getJson("/api/exam-questions/{$q->id}/image")->assertForbidden();
        }

        $this->class->restore();
        Sanctum::actingAs($this->owner);
        $this->getJson("/api/exams/{$exam->id}")->assertOk();
    }

    // ================================================================ sets

    public function test_set_flow_over_http(): void
    {
        $exam = $this->exam();
        $setA = $exam->sets()->first();
        $this->question($setA);
        Sanctum::actingAs($this->owner);

        $b = $this->postJson("/api/exams/{$exam->id}/sets")->assertCreated()
            ->assertJsonPath('data.label', 'B')->assertJsonPath('data.question_count', 0);
        $this->assertNotSame($setA->qr_token, $b->json('data.qr_token'));

        $this->deleteJson('/api/exam-sets/' . $b->json('data.id'))->assertNoContent();
        $this->assertEquals(1, ExamSet::where('exam_id', $exam->id)->count());

        // The last set stays.
        $this->deleteJson("/api/exam-sets/{$setA->id}")->assertStatus(422)->assertJsonValidationErrors('set');
        $this->assertEquals(1, ExamQuestion::count());
    }

    // ================================================================ questions

    public function test_a_question_is_created_edited_and_deleted_over_http(): void
    {
        $set = $this->exam()->sets()->first();
        Sanctum::actingAs($this->owner);

        $a = $this->postJson("/api/exam-sets/{$set->id}/questions", $this->questionPayload(['correct_choice' => 'c']))
            ->assertCreated()
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.correct_choice', 'C')
            ->assertJsonPath('data.has_image', false)
            ->assertJsonPath('data.image_url', null)
            ->assertJsonPath('data.competency.id', $this->comp->id);
        $b = $this->postJson("/api/exam-sets/{$set->id}/questions", $this->questionPayload())->assertCreated()
            ->assertJsonPath('data.position', 2);

        $this->putJson('/api/exam-questions/' . $a->json('data.id'), $this->questionPayload(['body' => 'Edited', 'correct_choice' => 'D']))
            ->assertOk()->assertJsonPath('data.body', 'Edited')->assertJsonPath('data.correct_choice', 'D');

        $this->deleteJson('/api/exam-questions/' . $a->json('data.id'))->assertNoContent();
        $this->assertEquals(1, ExamQuestion::find($b->json('data.id'))->position);
    }

    public function test_question_validation(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        Sanctum::actingAs($this->owner);
        $url = "/api/exam-sets/{$set->id}/questions";

        $this->postJson($url, [])->assertStatus(422)
            ->assertJsonValidationErrors(['body', 'choices', 'correct_choice', 'competency_id']);
        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b', 'c']]))->assertStatus(422)->assertJsonValidationErrors('choices');
        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b', 'c', 'd', 'e']]))->assertStatus(422)->assertJsonValidationErrors('choices');
        $this->postJson($url, $this->questionPayload(['correct_choice' => 'E']))->assertStatus(422)->assertJsonValidationErrors('correct_choice');
        $this->postJson($url, $this->questionPayload(['correct_choice' => 'Z']))->assertStatus(422)->assertJsonValidationErrors('correct_choice');
        $this->postJson($url, $this->questionPayload(['body' => str_repeat('x', 2001)]))->assertStatus(422)->assertJsonValidationErrors('body');

        $theirs = $this->competency($this->other, 'Their competency');
        $this->postJson($url, $this->questionPayload(['competency_id' => $theirs->id]))
            ->assertStatus(422)->assertJsonValidationErrors('competency_id');

        $this->assertEquals(0, ExamQuestion::count());
    }

    public function test_the_fifty_one_question_is_refused(): void
    {
        $set = $this->exam()->sets()->first();
        for ($i = 0; $i < 50; $i++) {
            $this->question($set);
        }
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/exam-sets/{$set->id}/questions", $this->questionPayload())
            ->assertStatus(422)->assertJsonValidationErrors('body');
        $this->assertEquals(50, ExamQuestion::count());
    }

    public function test_reorder_over_http(): void
    {
        $set = $this->exam()->sets()->first();
        $a = $this->question($set);
        $b = $this->question($set);
        $c = $this->question($set);
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/exam-sets/{$set->id}/questions/order", ['ids' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJsonPath('data.questions.0.id', $c->id)
            ->assertJsonPath('data.questions.2.id', $b->id);

        $this->putJson("/api/exam-sets/{$set->id}/questions/order", ['ids' => [$a->id]])
            ->assertStatus(422)->assertJsonValidationErrors('ids');
        $this->putJson("/api/exam-sets/{$set->id}/questions/order", [])->assertStatus(422);
    }

    // ================================================================ competencies

    public function test_competencies_are_personal(): void
    {
        $theirs = $this->competency($this->other, 'Their competency');
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/competencies')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Solves linear equations');

        $created = $this->postJson('/api/competencies', ['name' => '  Reads a graph  ', 'subject_id' => Subject::first()->id])
            ->assertCreated()->assertJsonPath('data.name', 'Reads a graph')->assertJsonPath('data.subject.name', 'Mathematics');

        $this->postJson('/api/competencies', ['name' => 'reads a graph', 'subject_id' => Subject::first()->id])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/api/competencies', ['name' => ''])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/api/competencies', ['name' => 'X', 'subject_id' => 999999])->assertStatus(422)->assertJsonValidationErrors('subject_id');

        $this->putJson('/api/competencies/' . $created->json('data.id'), ['name' => 'Reads graphs'])
            ->assertOk()->assertJsonPath('data.name', 'Reads graphs');

        $this->putJson("/api/competencies/{$theirs->id}", ['name' => 'Taken'])->assertForbidden();
        $this->deleteJson("/api/competencies/{$theirs->id}")->assertForbidden();

        $this->deleteJson('/api/competencies/' . $created->json('data.id'))->assertNoContent();
    }

    public function test_a_competency_in_use_cannot_be_deleted(): void
    {
        $this->question($this->exam()->sets()->first());
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/competencies/{$this->comp->id}")
            ->assertStatus(422)->assertJsonValidationErrors('competency');
        $this->getJson('/api/competencies')->assertJsonPath('data.0.question_count', 1);
    }

    // ================================================================ audit

    public function test_an_admin_reading_is_audited_without_text_and_a_teacher_reading_is_not(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        $this->question($set, ['body' => 'ZZ-AUDIT-SECRET']);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/exams')->assertOk();
        $this->getJson("/api/exams/{$exam->id}")->assertOk();
        $this->getJson("/api/exam-sets/{$set->id}")->assertOk();
        $this->assertEquals(0, AuditLog::where('event', 'admin_read')->count());

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/exams')->assertOk();
        $this->assertEquals(1, AuditLog::where('event', 'admin_read')->count());
        $this->getJson("/api/exams/{$exam->id}")->assertOk();
        $this->getJson("/api/exam-sets/{$set->id}")->assertOk();
        $this->assertEquals(3, AuditLog::where('event', 'admin_read')->count());

        $row = AuditLog::where('event', 'admin_read')->latest('id')->first();
        $new = is_string($row->new_values) ? json_decode($row->new_values, true) : $row->new_values;
        $this->assertEquals('exam', $new['record_type']);
        $this->assertStringNotContainsString('ZZ-AUDIT-SECRET', json_encode(AuditLog::all()->toArray()));
    }

    public function test_an_admin_edit_over_http_is_a_correction(): void
    {
        $exam = $this->exam();
        $q = $this->question($exam->sets()->first());
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/exams/{$exam->id}", $this->examPayload(['title' => 'Fixed']))->assertOk();
        $this->putJson("/api/exam-questions/{$q->id}", $this->questionPayload(['body' => 'Fixed']))->assertOk();

        $this->assertEquals(2, AuditLog::where('event', 'admin_corrected')->count());
    }

    // ================================================================ images

    public function test_an_image_is_stored_resized_and_replaced(): void
    {
        $q = $this->question($this->exam()->sets()->first());
        Sanctum::actingAs($this->owner);

        $first = $this->post("/api/exam-questions/{$q->id}/image", [
            'image' => UploadedFile::fake()->image('graph.png', 2400, 1200),
            'alt' => '  A graph of y = 2x  ',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.has_image', true)
            ->assertJsonPath('data.image_alt', 'A graph of y = 2x');
        $this->assertStringStartsWith('/api/exam-questions/' . $q->id . '/image?v=', $first->json('data.image_url'));

        $path1 = $q->refresh()->image_path;
        Storage::disk('local')->assertExists($path1);
        $size = getimagesizefromstring(Storage::disk('local')->get($path1));
        $this->assertEquals(1600, $size[0]); // 2400 wide is scaled down to 1600
        $this->assertEquals(IMAGETYPE_PNG, $size[2]);

        // Replacing removes the old file.
        $this->post("/api/exam-questions/{$q->id}/image", [
            'image' => UploadedFile::fake()->image('second.jpg', 300, 200),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.image_alt', null);

        $path2 = $q->refresh()->image_path;
        $this->assertNotSame($path1, $path2);
        Storage::disk('local')->assertMissing($path1);
        Storage::disk('local')->assertExists($path2);
        $this->assertEquals(IMAGETYPE_JPEG, getimagesizefromstring(Storage::disk('local')->get($path2))[2]);

        // Removing deletes the file and clears the fields.
        $this->deleteJson("/api/exam-questions/{$q->id}/image")->assertNoContent();
        Storage::disk('local')->assertMissing($path2);
        $this->assertNull($q->refresh()->image_path);
        $this->assertNull($q->image_alt);
    }

    public function test_unsafe_or_oversized_images_are_rejected(): void
    {
        $q = $this->question($this->exam()->sets()->first());
        Sanctum::actingAs($this->owner);
        $url = "/api/exam-questions/{$q->id}/image";
        $headers = ['Accept' => 'application/json'];

        $svg = UploadedFile::fake()->createWithContent('shape.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');
        $this->post($url, ['image' => $svg], $headers)->assertStatus(422)->assertJsonValidationErrors('image');

        $php = UploadedFile::fake()->createWithContent('graph.png', '<?php echo "hello";');
        $this->post($url, ['image' => $php], $headers)->assertStatus(422)->assertJsonValidationErrors('image');

        $big = UploadedFile::fake()->image('big.png', 100, 100)->size(3000);
        $this->post($url, ['image' => $big], $headers)->assertStatus(422)->assertJsonValidationErrors('image');

        $this->post($url, [], $headers)->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertNull($q->refresh()->image_path);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_the_image_is_streamed_only_to_people_the_policy_allows(): void
    {
        $q = $this->question($this->exam()->sets()->first());
        Sanctum::actingAs($this->owner);
        $this->post("/api/exam-questions/{$q->id}/image", [
            'image' => UploadedFile::fake()->image('graph.png', 200, 100),
        ], ['Accept' => 'application/json'])->assertOk();

        $res = $this->get("/api/exam-questions/{$q->id}/image")->assertOk();
        $this->assertStringStartsWith('image/png', $res->headers->get('Content-Type'));
        $this->assertEquals('nosniff', $res->headers->get('X-Content-Type-Options'));

        Sanctum::actingAs($this->admin);
        $this->get("/api/exam-questions/{$q->id}/image")->assertOk();

        Sanctum::actingAs($this->other);
        $this->get("/api/exam-questions/{$q->id}/image")->assertForbidden();
        $this->post("/api/exam-questions/{$q->id}/image", [
            'image' => UploadedFile::fake()->image('x.png', 10, 10),
        ], ['Accept' => 'application/json'])->assertForbidden();
        $this->deleteJson("/api/exam-questions/{$q->id}/image")->assertForbidden();

        // A question without an image answers 404.
        Sanctum::actingAs($this->owner);
        $plain = $this->question($q->set);
        $this->get("/api/exam-questions/{$plain->id}/image")->assertNotFound();
    }

    public function test_deleting_a_question_or_a_set_removes_its_image_files(): void
    {
        $exam = $this->exam();
        $setA = $exam->sets()->first();
        $setB = app(ExamService::class)->addSet($this->owner, $exam);
        $qa = $this->question($setA);
        $qb = $this->question($setB);
        Sanctum::actingAs($this->owner);

        foreach ([$qa, $qb] as $q) {
            $this->post("/api/exam-questions/{$q->id}/image", [
                'image' => UploadedFile::fake()->image('g.png', 50, 50),
            ], ['Accept' => 'application/json'])->assertOk();
        }
        $this->assertCount(2, Storage::disk('local')->allFiles());

        $this->deleteJson("/api/exam-questions/{$qa->id}")->assertNoContent();
        $this->assertCount(1, Storage::disk('local')->allFiles());

        $this->deleteJson("/api/exam-sets/{$setB->id}")->assertNoContent();
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    // ================================================================ queries

    public function test_the_query_counts_do_not_grow_with_questions_or_exams(): void
    {
        $exam = $this->exam();
        $set = $exam->sets()->first();
        for ($i = 0; $i < 3; $i++) {
            $this->question($set);
        }
        Sanctum::actingAs($this->owner);

        $setFew = $this->queries(fn () => $this->getJson("/api/exam-sets/{$set->id}")->assertOk());
        $listFew = $this->queries(fn () => $this->getJson('/api/exams')->assertOk());

        for ($i = 0; $i < 12; $i++) {
            $this->question($set);
        }
        for ($i = 0; $i < 4; $i++) {
            $this->exam();
        }

        $setMany = $this->queries(fn () => $this->getJson("/api/exam-sets/{$set->id}")->assertOk()->assertJsonCount(15, 'data.questions'));
        $listMany = $this->queries(fn () => $this->getJson('/api/exams')->assertOk()->assertJsonCount(5, 'data'));

        $this->assertEquals($setFew, $setMany);
        $this->assertEquals($listFew, $listMany);
    }
    public function test_a_three_choice_exam_over_http(): void
    {
        Sanctum::actingAs($this->owner);

        $exam = $this->postJson('/api/exams', $this->examPayload(['choice_count' => 3]))
            ->assertCreated()->assertJsonPath('data.choice_count', 3);
        $url = '/api/exam-sets/' . $exam->json('data.sets.0.id') . '/questions';

        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b', 'c'], 'correct_choice' => 'C']))
            ->assertCreated()->assertJsonPath('data.correct_choice', 'C');
        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b', 'c'], 'correct_choice' => 'D']))
            ->assertStatus(422)->assertJsonValidationErrors('correct_choice');
        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b']]))
            ->assertStatus(422)->assertJsonValidationErrors('choices');
        $this->postJson($url, $this->questionPayload(['choices' => ['a', 'b', 'c', 'd']]))
            ->assertStatus(422)->assertJsonValidationErrors('choices');
    }
}
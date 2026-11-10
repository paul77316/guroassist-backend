<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamScanBatch;
use App\Models\ExamScanQuestionCount;
use App\Models\ExamSet;
use App\Models\GradeLevel;
use App\Models\MobileDevice;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CompetencyService;
use App\Services\ExamQuestionService;
use App\Services\ExamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class ExamScanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private ClassAssignment $class;

    private Exam $exam;

    private ExamSet $set;

    private ExamQuestion $question;

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

        $this->owner = User::factory()->create(['role' => 'teacher', 'email' => 'scan-owner@test.local']);
        $this->other = User::factory()->create(['role' => 'teacher', 'email' => 'scan-other@test.local']);
        $ownerProfile = Teacher::create(['user_id' => $this->owner->id, 'employee_id' => 'T-SCAN-OWNER']);
        $otherProfile = Teacher::create(['user_id' => $this->other->id, 'employee_id' => 'T-SCAN-OTHER']);

        $year = AcademicYear::create(['name' => '2030-2031', 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['name' => 'Grade 8', 'order' => 8]);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH']);
        $section = Section::create(['name' => 'Mabini', 'grade_level_id' => $grade->id, 'academic_year_id' => $year->id]);

        $this->class = ClassAssignment::create([
            'teacher_id' => $ownerProfile->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);
        ClassAssignment::create([
            'teacher_id' => $otherProfile->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);

        $competency = app(CompetencyService::class)->create($this->owner, ['name' => 'Solves equations']);
        $this->exam = app(ExamService::class)->create($this->owner, [
            'class_assignment_id' => $this->class->id,
            'title' => 'Quarter quiz',
            'exam_date' => '2030-03-20',
            'choice_count' => 4,
        ]);
        $this->set = $this->exam->sets()->firstOrFail();
        $this->question = app(ExamQuestionService::class)->create($this->owner, $this->set, [
            'body' => 'Private question text',
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'B',
            'competency_id' => $competency->id,
        ]);

        $this->travelTo(Carbon::parse('2030-03-10 10:00:00', 'Asia/Manila'));
    }

    public function test_mobile_qr_lookup_returns_layout_and_answer_key_without_question_text(): void
    {
        $this->actAsDevice($this->owner);

        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)
            ->assertOk()
            ->assertJsonPath('data.exam.choice_count', 4)
            ->assertJsonPath('data.set.label', 'A')
            ->assertJsonPath('data.layout.sheet.width_mm', 190)
            ->assertJsonPath('data.layout.marker_spacing_mm.horizontal', 180)
            ->assertJsonPath('data.layout.grid.rows_per_column', 13)
            ->assertJsonPath('data.questions.0.question_id', $this->question->id)
            ->assertJsonPath('data.questions.0.answer', 'B')
            ->assertJsonMissingPath('data.questions.0.body');

        $this->assertDatabaseMissing('audit_logs', ['event' => 'admin_read']);

        $this->class->update(['status' => 'archived']);
        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)->assertForbidden();
    }

    public function test_guests_web_sessions_and_other_teachers_cannot_use_scan_tokens(): void
    {
        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)->assertUnauthorized();

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)
            ->assertForbidden()
            ->assertJsonPath('code', 'mobile_token_required');

        $this->actAsDevice($this->other);
        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)->assertForbidden();
    }

    public function test_a_valid_batch_stores_only_class_totals_and_retries_idempotently(): void
    {
        $payload = $this->batchPayload();

        $first = $this->postJson('/api/mobile/exam-scan/batches', $payload)
            ->assertCreated()
            ->assertJsonPath('data.sheet_count', 2)
            ->assertJsonPath('data.questions.0.choice_counts.B', 2);

        $batchId = $first->json('data.id');
        $this->postJson('/api/mobile/exam-scan/batches', $payload)
            ->assertCreated()
            ->assertJsonPath('data.id', $batchId);

        $this->assertSame(1, ExamScanBatch::count());
        $this->assertSame(1, ExamScanQuestionCount::count());
        $this->assertDatabaseHas('exam_scan_question_counts', [
            'exam_scan_batch_id' => $batchId,
            'exam_question_id' => $this->question->id,
            'blank_count' => 0,
            'multiple_count' => 0,
        ]);
        $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token.'/batches')
            ->assertOk()
            ->assertJsonPath('data.0.sheet_count', 2);
    }

    public function test_count_totals_duplicate_check_and_question_scope_are_validated_before_writing(): void
    {
        $payload = $this->batchPayload();

        $this->postJson('/api/mobile/exam-scan/batches', array_merge($payload, [
            'questions' => [[
                'question_id' => $this->question->id,
                'choice_counts' => ['A' => 0, 'B' => 1, 'C' => 0, 'D' => 0],
                'blank_count' => 0,
                'multiple_count' => 0,
            ]],
        ]))->assertUnprocessable();

        $this->postJson('/api/mobile/exam-scan/batches', array_merge($payload, ['duplicate_check_confirmed' => false]))
            ->assertUnprocessable();

        $this->postJson('/api/mobile/exam-scan/batches', array_merge($payload, [
            'questions' => [[
                'question_id' => 999999,
                'choice_counts' => ['A' => 0, 'B' => 2, 'C' => 0, 'D' => 0],
                'blank_count' => 0,
                'multiple_count' => 0,
            ]],
        ]))->assertUnprocessable();

        $this->assertSame(0, ExamScanBatch::count());
        $this->assertSame(0, ExamScanQuestionCount::count());
    }

    public function test_batches_lock_exam_structure_until_the_batch_is_deleted(): void
    {
        app(ExamService::class)->addSet($this->owner, $this->exam);
        $payload = $this->batchPayload();
        $this->postJson('/api/mobile/exam-scan/batches', $payload)->assertCreated();

        $this->flushHeaders();
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/exams/'.$this->exam->id.'/sets')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('exam');
        $secondSet = $this->exam->sets()->whereKeyNot($this->set->id)->firstOrFail();
        $this->deleteJson('/api/exam-sets/'.$secondSet->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('exam');
        $this->putJson('/api/exam-questions/'.$this->question->id, [
            'body' => 'Changed question',
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'B',
            'competency_id' => $this->question->competency_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('exam');

        $this->actAsDevice($this->owner);
        $batch = ExamScanBatch::firstOrFail();
        $this->deleteJson('/api/mobile/exam-scan/batches/'.$batch->id)->assertNoContent();

        $this->assertSame(0, ExamScanBatch::count());
        $this->assertSame(0, ExamScanQuestionCount::count());
        $this->assertModelExists($this->question);
        $this->assertSame(1, AuditLog::where('event', 'scan_batch_deleted')->count());

        $this->flushHeaders();
        Sanctum::actingAs($this->owner);
        $this->putJson('/api/exam-questions/'.$this->question->id, [
            'body' => 'Changed question',
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'B',
            'competency_id' => $this->question->competency_id,
        ])->assertOk();
    }

    public function test_reusing_a_batch_id_with_changed_totals_is_rejected(): void
    {
        $payload = $this->batchPayload();
        $this->postJson('/api/mobile/exam-scan/batches', $payload)->assertCreated();

        $changed = $payload;
        $changed['questions'][0]['choice_counts']['A'] = 1;
        $changed['questions'][0]['choice_counts']['B'] = 1;

        $this->postJson('/api/mobile/exam-scan/batches', $changed)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_batch_id');

        $this->assertSame(1, ExamScanBatch::count());
    }

    public function test_a_stale_answer_key_fingerprint_cannot_be_saved_as_a_scan_batch(): void
    {
        $payload = $this->batchPayload();
        $this->flushHeaders();
        Sanctum::actingAs($this->owner);
        $this->putJson('/api/exam-questions/'.$this->question->id, [
            'body' => 'Private question text',
            'choices' => ['one', 'two', 'three', 'four'],
            'correct_choice' => 'C',
            'competency_id' => $this->question->competency_id,
        ])->assertOk();

        $this->actAsDevice($this->owner);
        $this->postJson('/api/mobile/exam-scan/batches', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('structure_hash');

        $this->assertSame(0, ExamScanBatch::count());
    }

    /** @return array<string, mixed> */
    private function batchPayload(): array
    {
        $this->actAsDevice($this->owner);
        $manifest = $this->getJson('/api/mobile/exam-scan/sets/'.$this->set->qr_token)->assertOk();

        return [
            'qr_token' => $this->set->qr_token,
            'structure_hash' => $manifest->json('data.structure_hash'),
            'client_batch_id' => (string) Str::uuid(),
            'sheet_count' => 2,
            'reviewed_mark_count' => 0,
            'duplicate_check_confirmed' => true,
            'questions' => [[
                'question_id' => $this->question->id,
                'choice_counts' => ['A' => 0, 'B' => 2, 'C' => 0, 'D' => 0],
                'blank_count' => 0,
                'multiple_count' => 0,
            ]],
        ];
    }

    private function actAsDevice(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $device = MobileDevice::create([
            'user_id' => $user->id,
            'device_uuid' => (string) Str::uuid(),
            'name' => 'Scan test phone',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $token = $user->createToken($device->tokenName(), [MobileDevice::ABILITY])->plainTextToken;
        $this->withToken($token);
    }
}

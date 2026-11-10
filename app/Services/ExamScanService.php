<?php

namespace App\Services;

use App\Models\ExamQuestion;
use App\Models\ExamScanBatch;
use App\Models\ExamSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** QR lookup and class-level scan totals. Student sheets and individual answers are never stored. */
class ExamScanService
{
    private const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    public function __construct(
        private ExamService $exams,
        private ExamStructureLock $structureLock,
    ) {}

    /** The QR token resolves to a set and its scanner layout, never to a student identity. */
    public function setForScan(User $user, string $token): array
    {
        $set = $this->findAccessibleSet($user, $token);
        $questions = $set->questions;

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['qr_token' => ['This exam set has no questions yet.']]);
        }

        return $this->setPayload($user, $set, $questions);
    }

    /** @return list<array{id: int, sheet_count: int, reviewed_mark_count: int, created_at: string}> */
    public function batchesForSet(User $user, string $token): array
    {
        $set = $this->findAccessibleSet($user, $token);
        $this->exams->auditRead($user, $set->exam);

        return $set->scanBatches()
            ->latest('id')
            ->get(['id', 'sheet_count', 'reviewed_mark_count', 'created_at'])
            ->map(fn (ExamScanBatch $batch): array => [
                'id' => (int) $batch->id,
                'sheet_count' => (int) $batch->sheet_count,
                'reviewed_mark_count' => (int) $batch->reviewed_mark_count,
                'created_at' => $batch->created_at->toIso8601String(),
            ])
            ->all();
    }

    /** @param array<string, mixed> $data */
    public function createBatch(User $user, array $data): ExamScanBatch
    {
        $set = $this->findAccessibleSet($user, $data['qr_token']);
        try {
            return DB::transaction(function () use ($user, $set, $data): ExamScanBatch {
                $exam = $this->structureLock->lock($set->exam);
                $set = ExamSet::query()->with(['exam', 'questions'])
                    ->whereKey($set->id)
                    ->firstOrFail();
                $this->authorizeSet($user, $set);
                $currentStructureHash = $this->structureHash($set);

                if (! hash_equals($currentStructureHash, $data['structure_hash'])) {
                    throw ValidationException::withMessages([
                        'structure_hash' => ['This exam changed after scanning began. Reload the set and scan the sheets again.'],
                    ]);
                }

                $normalized = $this->normalizeCounts($set, $data);
                $payloadHash = $this->payloadHash($user, $set, $data, $normalized);
                $existing = ExamScanBatch::query()->where('client_batch_id', $data['client_batch_id'])->first();
                if ($existing) {
                    return $this->replayOrFail($user, $existing, $payloadHash);
                }

                $batch = ExamScanBatch::create([
                    'exam_set_id' => $set->id,
                    'created_by' => $user->id,
                    'client_batch_id' => $data['client_batch_id'],
                    'payload_hash' => $payloadHash,
                    'sheet_count' => (int) $data['sheet_count'],
                    'reviewed_mark_count' => (int) $data['reviewed_mark_count'],
                    'duplicate_check_confirmed' => true,
                ]);

                foreach ($normalized as $row) {
                    $batch->questionCounts()->create($row);
                }

                AuditLogger::log($user, $exam, 'scan_batch_created', null, [
                    'batch_id' => $batch->id,
                    'set_id' => $set->id,
                    'sheet_count' => $batch->sheet_count,
                    'reviewed_mark_count' => $batch->reviewed_mark_count,
                    'duplicate_check_confirmed' => true,
                ]);

                return $batch->load('questionCounts');
            });
        } catch (UniqueConstraintViolationException) {
            $existing = ExamScanBatch::query()->with('set.exam', 'set.questions')
                ->where('client_batch_id', $data['client_batch_id'])
                ->first();
            if ($existing && $existing->set) {
                $set = $existing->set;
                $normalized = $this->normalizeCounts($set, $data);

                return $this->replayOrFail($user, $existing, $this->payloadHash($user, $set, $data, $normalized));
            }

            throw new \RuntimeException('The scan batch could not be saved.');
        }
    }

    public function deleteBatch(User $user, ExamScanBatch $batch): void
    {
        DB::transaction(function () use ($user, $batch): void {
            $current = ExamScanBatch::query()->with('set.exam')->lockForUpdate()->findOrFail($batch->id);
            $exam = $this->structureLock->lock($current->set->exam);

            AuditLogger::log($user, $exam, 'scan_batch_deleted', [
                'batch_id' => $current->id,
                'set_id' => $current->exam_set_id,
                'sheet_count' => $current->sheet_count,
                'reviewed_mark_count' => $current->reviewed_mark_count,
            ], null);

            $current->delete();
        });
    }

    /** @return array<string, mixed> */
    public function batchPayload(ExamScanBatch $batch): array
    {
        return [
            'id' => (int) $batch->id,
            'exam_set_id' => (int) $batch->exam_set_id,
            'sheet_count' => (int) $batch->sheet_count,
            'reviewed_mark_count' => (int) $batch->reviewed_mark_count,
            'duplicate_check_confirmed' => (bool) $batch->duplicate_check_confirmed,
            'created_at' => $batch->created_at->toIso8601String(),
            'questions' => $batch->questionCounts->map(fn ($row): array => [
                'question_id' => (int) $row->exam_question_id,
                'choice_counts' => $row->choice_counts,
                'blank_count' => (int) $row->blank_count,
                'multiple_count' => (int) $row->multiple_count,
            ])->values()->all(),
        ];
    }

    private function findAccessibleSet(User $user, string $token): ExamSet
    {
        $set = ExamSet::query()->with(['exam.classAssignment', 'questions'])
            ->where('qr_token', $token)
            ->firstOrFail();

        $this->authorizeSet($user, $set);

        return $set;
    }

    private function authorizeSet(User $user, ExamSet $set): void
    {
        Gate::forUser($user)->authorize('view', $set);
    }

    /** @param Collection<int, ExamQuestion> $questions
     * @return array<string, mixed>
     */
    private function setPayload(User $user, ExamSet $set, Collection $questions): array
    {
        $exam = $set->exam;
        $this->exams->auditRead($user, $exam);
        $count = (int) $exam->choice_count;
        $size = 10;

        return [
            'exam' => [
                'title' => $exam->title,
                'exam_date' => $exam->exam_date?->format('Y-m-d'),
                'choice_count' => $count,
            ],
            'set' => ['id' => (int) $set->id, 'label' => $set->label],
            'structure_hash' => $this->structureHash($set),
            'layout' => [
                'version' => 1,
                'sheet' => ['width_mm' => 190, 'height_mm' => 260],
                'marker_size_mm' => $size,
                'marker_spacing_mm' => ['horizontal' => 180, 'vertical' => 250],
                'markers' => [
                    ['corner' => 'tl', 'x_mm' => 0, 'y_mm' => 0, 'size_mm' => $size],
                    ['corner' => 'tr', 'x_mm' => 180, 'y_mm' => 0, 'size_mm' => $size],
                    ['corner' => 'bl', 'x_mm' => 0, 'y_mm' => 250, 'size_mm' => $size],
                    ['corner' => 'br', 'x_mm' => 180, 'y_mm' => 250, 'size_mm' => $size],
                ],
                'qr' => ['x_mm' => 146, 'y_mm' => 12, 'size_mm' => 34],
                'grid' => [
                    'columns' => 4,
                    'rows_per_column' => 13,
                    'column_width_mm' => 42,
                    'origin_x_mm' => 11,
                    'first_bubble_x_mm' => 12,
                    'bubble_pitch_mm' => 6.5,
                    'bubble_diameter_mm' => 5,
                    'letter_row_y_mm' => 56,
                    'first_row_y_mm' => 62,
                    'row_pitch_mm' => 14.5,
                ],
            ],
            'questions' => $questions->values()->map(fn (ExamQuestion $question): array => [
                'question_id' => (int) $question->id,
                'position' => (int) $question->position,
                'answer' => $question->correct_choice,
            ])->all(),
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<array{exam_question_id: int, choice_counts: array<string, int>, blank_count: int, multiple_count: int}>
     */
    private function normalizeCounts(ExamSet $set, array $data): array
    {
        $questions = $set->questions->keyBy('id');
        $rows = collect($data['questions'])
            ->keyBy(fn (array $row): int => (int) $row['question_id']);

        if ($questions->isEmpty() || $rows->count() !== $questions->count()
            || $rows->keys()->diff($questions->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'questions' => ['Send counts for every question in this set exactly once.'],
            ]);
        }

        $letters = array_slice(self::LETTERS, 0, (int) $set->exam->choice_count);
        $sheetCount = (int) $data['sheet_count'];
        $normalized = [];

        foreach ($questions as $question) {
            $row = $rows->get($question->id);
            $choiceCounts = $row['choice_counts'];
            $keys = array_keys($choiceCounts);
            sort($keys);
            $expectedKeys = $letters;
            sort($expectedKeys);

            if ($keys !== $expectedKeys) {
                throw ValidationException::withMessages([
                    'questions' => ['Choice totals must include only the available answer letters.'],
                ]);
            }

            $counts = [];
            foreach ($letters as $letter) {
                $counts[$letter] = (int) $choiceCounts[$letter];
            }
            $blankCount = (int) $row['blank_count'];
            $multipleCount = (int) $row['multiple_count'];

            if (array_sum($counts) + $blankCount + $multipleCount !== $sheetCount) {
                throw ValidationException::withMessages([
                    'questions' => ['For each question, answer, blank and multiple totals must equal the sheet count.'],
                ]);
            }

            $normalized[] = [
                'exam_question_id' => (int) $question->id,
                'choice_counts' => $counts,
                'blank_count' => $blankCount,
                'multiple_count' => $multipleCount,
            ];
        }

        if ((int) $data['reviewed_mark_count'] > $sheetCount * $questions->count()) {
            throw ValidationException::withMessages([
                'reviewed_mark_count' => ['The reviewed mark count cannot exceed all scanned answers.'],
            ]);
        }

        return $normalized;
    }

    private function replayOrFail(User $user, ExamScanBatch $batch, string $payloadHash): ExamScanBatch
    {
        if ((int) $batch->created_by !== (int) $user->id || ! hash_equals($batch->payload_hash, $payloadHash)) {
            throw ValidationException::withMessages([
                'client_batch_id' => ['This batch id was already used for different scan data.'],
            ]);
        }

        return $batch->load('questionCounts');
    }

    /** @param array<string, mixed> $data
     * @param  list<array<string, mixed>>  $normalized
     */
    private function payloadHash(User $user, ExamSet $set, array $data, array $normalized): string
    {
        return hash('sha256', json_encode([
            'user_id' => (int) $user->id,
            'exam_set_id' => (int) $set->id,
            'structure_hash' => $data['structure_hash'],
            'sheet_count' => (int) $data['sheet_count'],
            'reviewed_mark_count' => (int) $data['reviewed_mark_count'],
            'duplicate_check_confirmed' => true,
            'questions' => $normalized,
        ], JSON_THROW_ON_ERROR));
    }

    private function structureHash(ExamSet $set): string
    {
        return hash('sha256', json_encode([
            'exam_set_id' => (int) $set->id,
            'choice_count' => (int) $set->exam->choice_count,
            'layout_version' => 1,
            'questions' => $set->questions->map(fn (ExamQuestion $question): array => [
                'id' => (int) $question->id,
                'position' => (int) $question->position,
                'answer' => $question->correct_choice,
                'competency_id' => (int) $question->competency_id,
                'content_hash' => hash('sha256', json_encode([
                    $question->body,
                    $question->choices,
                    $question->image_path,
                ], JSON_THROW_ON_ERROR)),
            ])->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }
}

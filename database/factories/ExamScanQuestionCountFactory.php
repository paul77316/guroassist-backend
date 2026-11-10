<?php

namespace Database\Factories;

use App\Models\ExamQuestion;
use App\Models\ExamScanBatch;
use App\Models\ExamScanQuestionCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamScanQuestionCount>
 */
class ExamScanQuestionCountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_scan_batch_id' => fn (): int => (int) ExamScanBatch::query()->inRandomOrder()->value('id'),
            'exam_question_id' => fn (): int => (int) ExamQuestion::query()->inRandomOrder()->value('id'),
            'choice_counts' => ['A' => 1, 'B' => 0, 'C' => 0, 'D' => 0],
            'blank_count' => 0,
            'multiple_count' => 0,
        ];
    }

    public function forQuestion(ExamScanBatch $batch, ExamQuestion $question): static
    {
        return $this->state(fn (): array => [
            'exam_scan_batch_id' => $batch->id,
            'exam_question_id' => $question->id,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\ExamScanBatch;
use App\Models\ExamSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamScanBatch>
 */
class ExamScanBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_set_id' => fn (): int => (int) ExamSet::query()->inRandomOrder()->value('id'),
            'created_by' => User::factory(),
            'client_batch_id' => (string) Str::uuid(),
            'payload_hash' => hash('sha256', (string) Str::uuid()),
            'sheet_count' => fake()->numberBetween(1, 30),
            'reviewed_mark_count' => 0,
            'duplicate_check_confirmed' => true,
        ];
    }

    public function forSet(ExamSet $set): static
    {
        return $this->state(fn (): array => ['exam_set_id' => $set->id]);
    }
}

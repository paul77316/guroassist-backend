<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class ExamStructureLock
{
    public function lock(Exam $exam): Exam
    {
        return Exam::query()->lockForUpdate()->findOrFail($exam->id);
    }

    public function assertUnlocked(Exam $exam): void
    {
        if ($exam->scanBatches()->exists()) {
            throw ValidationException::withMessages([
                'exam' => ['This exam has scan batches. Delete its scan batches before changing its sets or questions.'],
            ]);
        }
    }

    public function lockForStructuralEdit(Exam $exam): Exam
    {
        $locked = $this->lock($exam);
        $this->assertUnlocked($locked);

        return $locked;
    }
}

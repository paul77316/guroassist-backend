<?php

namespace App\Policies;

use App\Models\ExamQuestion;
use App\Models\User;

/** A question follows its exam, through its set. */
class ExamQuestionPolicy
{
    public function view(User $user, ExamQuestion $question): bool
    {
        $exam = $question->set?->exam;

        return $exam !== null && $user->can('view', $exam);
    }

    public function update(User $user, ExamQuestion $question): bool
    {
        $exam = $question->set?->exam;

        return $exam !== null && $user->can('update', $exam);
    }

    public function delete(User $user, ExamQuestion $question): bool
    {
        return $this->update($user, $question);
    }
}
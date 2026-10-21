<?php

namespace App\Services;

use App\Models\Competency;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Questions inside a set. Every write checks the rules here, whatever the client sent.
 * Audit values hold ids, positions and "text changed" flags, never question or choice text.
 */
class ExamQuestionService
{
    public const MAX_QUESTIONS = 50;
    public const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    public function create(User $user, ExamSet $set, array $data): ExamQuestion
    {
        return DB::transaction(function () use ($user, $set, $data) {
            // Lock the set so two requests cannot take the same position or pass the limit together.
            $locked = ExamSet::query()->lockForUpdate()->find($set->id);
            $exam = $locked?->exam;
            if (! $exam) {
                throw ValidationException::withMessages(['set' => ['This set is not available.']]);
            }

            $clean = $this->validated($exam, $data);

            $count = $locked->questions()->count();
            if ($count >= self::MAX_QUESTIONS) {
                throw ValidationException::withMessages(['body' => ['A set can have at most ' . self::MAX_QUESTIONS . ' questions.']]);
            }

            $q = new ExamQuestion($clean);
            $q->exam_set_id = $locked->id;
            $q->position = $count + 1; // positions stay 1..n with no gaps
            $q->save();

            AuditLogger::log($user, $exam, 'question_added', null, [
                'set_id' => $locked->id,
                'question_id' => $q->id,
                'position' => $q->position,
                'competency_id' => $q->competency_id,
            ]);

            return $q;
        });
    }

    public function update(User $user, ExamQuestion $question, array $data): ExamQuestion
    {
        return DB::transaction(function () use ($user, $question, $data) {
            $row = ExamQuestion::query()->lockForUpdate()->find($question->id);
            $exam = $row?->set?->exam;
            if (! $exam) {
                throw ValidationException::withMessages(['question' => ['This question is not available.']]);
            }

            $clean = $this->validated($exam, $data);
            $flags = [
                'text_changed' => $clean['body'] !== $row->body,
                'choices_changed' => $clean['choices'] !== $row->choices,
                'correct_changed' => $clean['correct_choice'] !== $row->correct_choice,
                'competency_changed' => $clean['competency_id'] !== (int) $row->competency_id,
            ];
            $old = $this->values($row);

            $row->fill($clean)->save();

            $event = $user->id === $exam->author_id ? 'question_updated' : 'admin_corrected';
            AuditLogger::log($user, $exam, $event, $old, $this->values($row->refresh()) + $flags);

            return $row;
        });
    }

    /** Deletes the question and its image, then closes the gap in the positions. */
    public function delete(User $user, ExamQuestion $question): void
    {
        $path = null;

        DB::transaction(function () use ($user, $question, &$path) {
            $row = ExamQuestion::query()->lockForUpdate()->find($question->id);
            $exam = $row?->set?->exam;
            if (! $row || ! $exam) {
                throw ValidationException::withMessages(['question' => ['This question is not available.']]);
            }

            $path = $row->image_path;
            $setId = $row->exam_set_id;

            AuditLogger::log($user, $exam, 'question_deleted', $this->values($row), null);
            $row->delete();

            $this->renumber($setId);
        });

        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /** ids: every question id of the set, in the new order. */
    public function reorder(User $user, ExamSet $set, array $ids): void
    {
        DB::transaction(function () use ($user, $set, $ids) {
            $locked = ExamSet::query()->lockForUpdate()->find($set->id);
            $exam = $locked?->exam;
            if (! $exam) {
                throw ValidationException::withMessages(['set' => ['This set is not available.']]);
            }

            $ids = array_map('intval', $ids);
            $existing = $locked->questions()->pluck('id')->map(fn ($i) => (int) $i)->all();

            if (count($ids) !== count($existing)
                || count(array_unique($ids)) !== count($ids)
                || array_diff($existing, $ids) !== []) {
                throw ValidationException::withMessages(['ids' => ['The list must contain every question of the set exactly once.']]);
            }

            foreach ($ids as $i => $id) {
                ExamQuestion::query()->whereKey($id)->update(['position' => $i + 1]);
            }

            AuditLogger::log($user, $exam, 'questions_reordered', null, [
                'set_id' => $locked->id,
                'question_ids' => $ids,
            ]);
        });
    }

    // ---------------------------------------------------------------- internals

    /** The choice count, correct choice and competency are checked against the exam, not trusted from the client. */
    private function validated(Exam $exam, array $data): array
    {
        $errors = [];

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            $errors['body'][] = 'The question needs text.';
        }

        $choices = array_values(array_map(fn ($c) => trim((string) $c), (array) ($data['choices'] ?? [])));
        if (count($choices) !== (int) $exam->choice_count) {
            $errors['choices'][] = 'This exam uses ' . $exam->choice_count . ' choices.';
        } elseif (in_array('', $choices, true)) {
            $errors['choices'][] = 'Every choice needs text.';
        }

        $letters = array_slice(self::LETTERS, 0, (int) $exam->choice_count);
        $correct = strtoupper((string) ($data['correct_choice'] ?? ''));
        if (! in_array($correct, $letters, true)) {
            $errors['correct_choice'][] = 'Choose one of the available choices.';
        }

        $competencyId = (int) ($data['competency_id'] ?? 0);
        $owned = $competencyId > 0 && Competency::query()
            ->where('owner_id', $exam->author_id)
            ->whereKey($competencyId)
            ->exists();
        if (! $owned) {
            $errors['competency_id'][] = 'Choose one of your competencies.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'body' => $body,
            'choices' => $choices,
            'correct_choice' => $correct,
            'competency_id' => $competencyId,
        ];
    }

    private function renumber(int $setId): void
    {
        $ids = ExamQuestion::query()
            ->where('exam_set_id', $setId)
            ->orderBy('position')->orderBy('id')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            ExamQuestion::query()->whereKey($id)->update(['position' => $i + 1]);
        }
    }

    private function values(ExamQuestion $q): array
    {
        return [
            'question_id' => $q->id,
            'set_id' => $q->exam_set_id,
            'position' => $q->position,
            'competency_id' => $q->competency_id,
            'has_image' => $q->image_path !== null,
        ];
    }
}
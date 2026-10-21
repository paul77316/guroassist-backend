<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A question for the builder. The correct choice is only ever sent to the author and admins. */
class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_set_id' => $this->exam_set_id,
            'position' => $this->position,
            'body' => $this->body,
            'choices' => $this->choices,
            'correct_choice' => $this->correct_choice,
            'competency' => $this->whenLoaded('competency', fn () => $this->competency
                ? ['id' => $this->competency->id, 'name' => $this->competency->name]
                : null),
            'has_image' => $this->image_path !== null,
            'image_alt' => $this->image_alt,
            'image_url' => $this->image_path !== null
                ? '/api/exam-questions/' . $this->id . '/image?v=' . ($this->updated_at?->timestamp ?? 0)
                : null,
        ];
    }
}
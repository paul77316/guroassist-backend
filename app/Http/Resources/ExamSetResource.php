<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A set, with its questions when they are loaded. */
class ExamSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'label' => $this->label,
            'qr_token' => $this->qr_token,
            'exam' => $this->whenLoaded('exam', fn () => [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
                'choice_count' => $this->exam->choice_count,
            ]),
            'question_count' => $this->resource->questions_count
                ?? ($this->resource->relationLoaded('questions') ? $this->resource->questions->count() : null),
            'questions' => $this->whenLoaded('questions', fn () => ExamQuestionResource::collection($this->questions)),
        ];
    }
}
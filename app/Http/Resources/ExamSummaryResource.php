<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** List row: the exam and its counts. No question text; the detail endpoints carry it. */
class ExamSummaryResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'exam_date' => $this->ymd($this->exam_date),
            'choice_count' => $this->choice_count,
            'class' => $this->classInfo(),
            'set_count' => $this->resource->sets_count ?? null,
            'question_count' => $this->resource->questions_count ?? null,
            'deleted' => $this->trashed(),
            'author' => $this->authorName($request),
        ];
    }
}
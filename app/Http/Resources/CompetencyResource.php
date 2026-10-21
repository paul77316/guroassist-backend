<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject
                ? ['id' => $this->subject->id, 'name' => $this->subject->name]
                : null),
            'question_count' => $this->resource->questions_count ?? null,
        ];
    }
}
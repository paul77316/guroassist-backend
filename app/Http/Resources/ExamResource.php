<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Detail: the list fields plus the sets (with their QR tokens and question counts). */
class ExamResource extends ExamSummaryResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'sets' => $this->whenLoaded('sets', fn () => $this->sets->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'qr_token' => $s->qr_token,
                'question_count' => $s->questions_count ?? null,
            ])->values()),
        ];
    }
}
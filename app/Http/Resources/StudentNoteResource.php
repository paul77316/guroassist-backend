<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Detail: the list fields plus the full note text. */
class StudentNoteResource extends StudentNoteSummaryResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + ['body' => $this->body];
    }
}

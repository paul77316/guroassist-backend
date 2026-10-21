<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Detail: the list fields plus the reason and summary text. */
class ParentCommunicationResource extends ParentCommunicationSummaryResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'reason' => $this->reason,
            'summary' => $this->summary,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** A row in the cross-student list: the summary fields plus the student and the completion date. No free text. */
class InterventionListResource extends InterventionSummaryResource
{
    public function toArray(Request $request): array
    {
        $completedOn = $this->status === 'completed' && $this->completed_on
            ? substr((string) $this->completed_on, 0, 10)
            : null;

        return parent::toArray($request) + [
            'student' => [
                'id' => $this->student->id,
                'student_number' => $this->student->student_id,
                'full_name' => $this->student->full_name,
            ],
            'completed_on' => $completedOn,
        ];
    }
}

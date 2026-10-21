<?php

namespace App\Http\Resources;

use App\Support\InterventionStatus;
use Illuminate\Http\Request;

/**
 * Detail: the list fields plus the free text, the student and the follow-up history.
 * Expects student, followups and the class relations loaded by the controller.
 */
class InterventionResource extends InterventionSummaryResource
{
    public function toArray(Request $request): array
    {
        $next = InterventionStatus::TRANSITIONS[$this->status] ?? [];
        // A follow-up may also keep these two statuses (see InterventionService::addEntry).
        if (in_array($this->status, [InterventionStatus::IN_PROGRESS, InterventionStatus::REQUIRES_FOLLOW_UP], true)) {
            $next[] = $this->status;
        }

        return parent::toArray($request) + [
            'concern' => $this->concern,
            'notes' => $this->notes,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'student_number' => $this->student->student_id,
                'full_name' => $this->student->full_name,
            ]),
            'next_statuses' => array_map(
                fn ($s) => ['value' => $s, 'label' => InterventionStatus::label($s)],
                $next
            ),
            'followups' => $this->whenLoaded('followups', fn () => InterventionFollowupResource::collection($this->followups)->resolve($request)),
        ];
    }
}

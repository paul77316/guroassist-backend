<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A follow-up that needs attention. No free text; the teacher opens the intervention for details. */
class DueFollowUpResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        $followUp = $this->ymd($this->follow_up_date);

        return [
            'id' => $this->id,
            'kind' => 'intervention',
            'student' => [
                'id' => $this->student->id,
                'student_number' => $this->student->student_id,
                'full_name' => $this->student->full_name,
            ],
            'class' => $this->classInfo(),
            'concern_type_label' => InterventionVocabulary::label('CONCERN_TYPES', $this->concern_type),
            'follow_up_date' => $followUp,
            'follow_up_state' => $this->followUpState($followUp, $this->status),
            'status' => $this->status,
            'status_label' => InterventionStatus::label($this->status),
        ];
    }
}

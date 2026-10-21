<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** List row. Deliberately has no free text (concern, notes, progress); the detail endpoint carries it. */
class InterventionSummaryResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        $followUp = $this->ymd($this->follow_up_date);

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class' => $this->classInfo(),
            'concern_type' => $this->concern_type,
            'concern_type_label' => InterventionVocabulary::label('CONCERN_TYPES', $this->concern_type),
            'intervention_type' => $this->intervention_type,
            'intervention_type_label' => InterventionVocabulary::label('INTERVENTION_TYPES', $this->intervention_type),
            'intervention_date' => $this->ymd($this->intervention_date),
            'follow_up_date' => $followUp,
            'follow_up_done_at' => $this->ymd($this->follow_up_done_at),
            'follow_up_state' => $this->communicationFollowUpState($followUp, $this->ymd($this->follow_up_done_at)),
            'follow_up_state' => $this->followUpState($followUp, $this->status),
            'status' => $this->status,
            'status_label' => InterventionStatus::label($this->status),
            'outcome' => $this->outcome,
            'outcome_label' => InterventionVocabulary::label('OUTCOMES', $this->outcome),
            'deleted' => $this->trashed(),
            'author' => $this->authorName($request),
        ];
    }
}

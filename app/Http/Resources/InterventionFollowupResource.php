<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One history entry. Only returned inside the intervention detail. */
class InterventionFollowupResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_type' => $this->entry_type,
            'followup_date' => $this->ymd($this->followup_date),
            'from_status' => $this->from_status,
            'from_status_label' => InterventionStatus::label($this->from_status),
            'to_status' => $this->to_status,
            'to_status_label' => InterventionStatus::label($this->to_status),
            'progress' => $this->progress,
            'notes' => $this->notes,
            'next_follow_up_date' => $this->ymd($this->next_follow_up_date),
            'outcome' => $this->outcome,
            'outcome_label' => InterventionVocabulary::label('OUTCOMES', $this->outcome),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A parent-contact follow-up that needs attention. No reason or summary text; the detail window has it. */
class DueCommunicationFollowUpResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        $followUp = $this->ymd($this->follow_up_date);

        return [
            'id' => $this->id,
            'kind' => 'communication',
            'student' => [
                'id' => $this->student->id,
                'student_number' => $this->student->student_id,
                'full_name' => $this->student->full_name,
            ],
            'class' => $this->classInfo(),
            'method_label' => InterventionVocabulary::label('METHODS', $this->method),
            'contact_date' => $this->ymd($this->contact_date),
            'follow_up_date' => $followUp,
            'follow_up_state' => $this->communicationFollowUpState($followUp, $this->ymd($this->follow_up_done_at)),
        ];
    }
}

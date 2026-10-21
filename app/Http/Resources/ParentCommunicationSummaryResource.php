<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** List row. No reason or summary text; the detail endpoint carries it. */
class ParentCommunicationSummaryResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        $followUp = $this->ymd($this->follow_up_date);

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class' => $this->classInfo(),
            'contact_date' => $this->ymd($this->contact_date),
            'method' => $this->method,
            'method_label' => InterventionVocabulary::label('METHODS', $this->method),
            'follow_up_date' => $followUp,
            'follow_up_done_at' => $this->ymd($this->follow_up_done_at),
            'follow_up_state' => $this->communicationFollowUpState($followUp, $this->ymd($this->follow_up_done_at)),
            'deleted' => $this->trashed(),
            'author' => $this->authorName($request),
        ];
    }
}

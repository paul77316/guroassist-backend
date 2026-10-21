<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = Carbon::now(config('monitoring.timezone'))->toDateString();
        $due = $this->due_date->format('Y-m-d');

        return [
            'id' => $this->id,
            'class_assignment_id' => $this->class_assignment_id,
            'title' => $this->title,
            'description' => $this->description,
            'assigned_date' => $this->assigned_date->format('Y-m-d'),
            'due_date' => $due,
            'is_past_due' => $due < $today,
        ];
    }
}

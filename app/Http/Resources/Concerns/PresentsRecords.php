<?php

namespace App\Http\Resources\Concerns;

use App\Support\InterventionStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Small helpers shared by the notes, interventions and communications resources. */
trait PresentsRecords
{
    /** School "today" (never APP_TIMEZONE). */
    protected function schoolToday(): string
    {
        return Carbon::now(config('monitoring.timezone'))->toDateString();
    }

    /** The class, or null for a general record. Needs classAssignment.section.gradeLevel + subject eager loaded. */
    protected function classInfo(): ?array
    {
        $ca = $this->resource->relationLoaded('classAssignment') ? $this->resource->classAssignment : null;
        if (! $ca) {
            return null;
        }

        $grade = $ca->section?->gradeLevel?->name;
        $section = $ca->section?->name;

        return [
            'id' => $ca->id,
            'label' => trim(($grade ? "$grade - " : '') . $section),
            'subject' => $ca->subject?->name,
        ];
    }

    /** overdue | due_today | upcoming, or null when there is no active due date. Calculated, never stored. */
    protected function followUpState(?string $date, ?string $status): ?string
    {
        if ($date === null || $status === InterventionStatus::COMPLETED) {
            return null;
        }
        $today = $this->schoolToday();

        return $date < $today ? 'overdue' : ($date === $today ? 'due_today' : 'upcoming');
    }
        /** done | overdue | due_today | upcoming, or null with no follow-up date. Calculated, never stored. */
    protected function communicationFollowUpState(?string $date, ?string $doneAt): ?string
    {
        if ($date === null) {
            return null;
        }
        if ($doneAt !== null) {
            return 'done';
        }
        $today = $this->schoolToday();

        return $date < $today ? 'overdue' : ($date === $today ? 'due_today' : 'upcoming');
    }

    /** Only admins see who wrote a record (teachers only ever see their own). */
    protected function authorName(Request $request): ?string
    {
        return $request->user()?->isAdmin() ? $this->resource->author?->name : null;
    }

    protected function ymd($value): ?string
    {
        return $value?->format('Y-m-d');
    }
}

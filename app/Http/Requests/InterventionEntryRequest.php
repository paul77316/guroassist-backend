<?php

namespace App\Http\Requests;

use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A follow-up, reschedule, completion or reopen. The entry type is decided by the server from the
 * transition; the client only says what it wants. Transition rules live in InterventionService.
 */
class InterventionEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; } // policy 'addEntry' is checked in the controller

    public function rules(): array
    {
        return [
            'kind' => ['nullable', Rule::in(['followup', 'reschedule'])],
            'followup_date' => ['required', 'date_format:Y-m-d'],
            'to_status' => ['nullable', Rule::in(InterventionStatus::ALL)],
            'progress' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_follow_up_date' => ['nullable', 'date_format:Y-m-d'],
            'outcome' => ['nullable', Rule::in(InterventionVocabulary::keys('OUTCOMES'))],
            // The status the screen was showing; a mismatch means another tab changed it.
            'expected_status' => ['nullable', Rule::in(InterventionStatus::ALL)],
        ];
    }
}

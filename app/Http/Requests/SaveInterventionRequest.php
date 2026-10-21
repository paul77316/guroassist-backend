<?php

namespace App\Http\Requests;

use App\Support\InterventionVocabulary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and edit. On edit, class_assignment_id and follow_up_date are accepted but ignored by the
 * service (the class never changes; the follow-up date changes only through a follow-up entry).
 * Status, outcome and author are not accepted here at all.
 */
class SaveInterventionRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller (policy) and the service

    public function rules(): array
    {
        return [
            // Existence and ownership of the class are checked by the service with one neutral message.
            'class_assignment_id' => ['nullable', 'integer'],
            'concern_type' => ['required', Rule::in(InterventionVocabulary::keys('CONCERN_TYPES'))],
            'concern' => ['required', 'string', 'max:2000'],
            'intervention_type' => ['required', Rule::in(InterventionVocabulary::keys('INTERVENTION_TYPES'))],
            'intervention_date' => ['required', 'date_format:Y-m-d'],
            'follow_up_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:intervention_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

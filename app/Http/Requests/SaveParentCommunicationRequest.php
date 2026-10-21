<?php

namespace App\Http\Requests;

use App\Support\InterventionVocabulary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create and edit. This logs contact that already happened; it sends nothing. */
class SaveParentCommunicationRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller and the service

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['nullable', 'integer'],
            'contact_date' => ['required', 'date_format:Y-m-d'],
            'method' => ['required', Rule::in(InterventionVocabulary::keys('METHODS'))],
            'reason' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:contact_date'],
        ];
    }
}

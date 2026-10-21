<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Create and edit a competency. */
class SaveCompetencyRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ];
    }
}
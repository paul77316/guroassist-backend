<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Create and edit. On edit, class_assignment_id is ignored by the service. */
class SaveStudentNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller and the service

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}

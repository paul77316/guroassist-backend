<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The new order: every question id of the set, once each. The service checks the list is complete. */
class ReorderExamQuestionsRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['integer'],
        ];
    }
}
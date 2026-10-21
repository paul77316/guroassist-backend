<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool { return true; } // scoped to the signed-in teacher in the controller

    public function rules(): array
    {
        return [
            'in_app_enabled' => ['required', 'boolean'],
            'include_students_to_review' => ['required', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is done in the controller via the policy
    }

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['required', 'integer', 'exists:class_assignments,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
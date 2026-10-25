<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin-only, checked in the controller
    }

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'in:present,absent,late,excused'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
            // The version the admin was looking at (0 when no mark exists yet).
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}

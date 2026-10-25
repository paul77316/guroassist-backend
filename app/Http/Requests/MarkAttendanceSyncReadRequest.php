<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceSyncReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->teacher;
    }

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['required', 'integer', 'exists:class_assignments,id'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_assignment_id' => ['required', 'integer', 'exists:class_assignments,id'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . now(config('monitoring.timezone'))->toDateString()],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'records.*.status' => ['present', 'nullable', 'in:present,absent,late,excused'],
            'records.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
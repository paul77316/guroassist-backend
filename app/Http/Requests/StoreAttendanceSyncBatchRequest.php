<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Shape only. Access, enrollment, archive and date checks are per operation, so one bad row never fails the batch. */
class StoreAttendanceSyncBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:200'],
            'operations.*.operation_id' => ['required', 'uuid', 'distinct'],
            'operations.*.class_assignment_id' => ['required', 'integer'],
            'operations.*.student_id' => ['required', 'integer'],
            'operations.*.date' => ['required', 'date_format:Y-m-d'],
            'operations.*.status' => ['required', 'in:present,absent,late,excused'],
            'operations.*.remarks' => ['nullable', 'string', 'max:255'],
            'operations.*.base_version' => ['required', 'integer', 'min:0'],
        ];
    }
}

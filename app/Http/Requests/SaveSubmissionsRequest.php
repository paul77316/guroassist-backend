<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSubmissionsRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked with the recordSubmissions policy in the controller

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1', 'max:300'],
            'records.*.student_id' => ['required', 'integer'],
            // The client never sends "late": the server decides it from the date.
            'records.*.outcome' => ['required', 'in:submitted,excused,clear'],
            'records.*.submitted_date' => ['nullable', 'date_format:Y-m-d'],
            'records.*.feedback' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

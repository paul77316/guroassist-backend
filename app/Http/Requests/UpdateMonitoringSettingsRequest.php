<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitoringSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; } // scoped to the signed-in teacher in the controller

    public function rules(): array
    {
        return [
            'attendance_rate_threshold' => ['required', 'numeric', 'min:1', 'max:100'],
            'consecutive_absence_threshold' => ['required', 'integer', 'min:1', 'max:30'],
            'min_days_for_rate' => ['required', 'integer', 'min:1', 'max:60'],
            'missing_assignments_threshold' => ['required', 'integer', 'min:1', 'max:50'],
            'guardian_email_enabled' => ['sometimes', 'boolean'],
            'notify_on_absent' => ['sometimes', 'boolean'],
            'notify_on_late' => ['sometimes', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool { return true; } // admin check is in the controller policy

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:20',
                Rule::unique('terms', 'sort_order')->where('academic_year_id', $this->input('academic_year_id'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['upcoming', 'active', 'completed'])],
        ];
    }
}
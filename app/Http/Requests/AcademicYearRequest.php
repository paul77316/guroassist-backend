<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $year = $this->route('academic_year');

        return $year ? $this->user()->can('update', $year) : $this->user()->can('create', AcademicYear::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20',
                Rule::unique('academic_years', 'name')->ignore($this->route('academic_year')?->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
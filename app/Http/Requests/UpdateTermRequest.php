<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $term = $this->route('term'); // academic year can't be changed after creation

        return [
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:20',
                Rule::unique('terms', 'sort_order')
                    ->where('academic_year_id', $term->academic_year_id)
                    ->ignore($term->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['upcoming', 'active', 'completed'])],
        ];
    }
}
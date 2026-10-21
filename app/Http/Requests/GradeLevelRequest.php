<?php

namespace App\Http\Requests;

use App\Models\GradeLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('grade_level');

        return $item ? $this->user()->can('update', $item) : $this->user()->can('create', GradeLevel::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->name)) {
            $this->merge(['name' => preg_replace('/\s+/', ' ', trim($this->name))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50',
                Rule::unique('grade_levels', 'name')->ignore($this->route('grade_level')?->id)],
            'order' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
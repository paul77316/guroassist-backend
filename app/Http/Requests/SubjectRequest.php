<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('subject');

        return $item ? $this->user()->can('update', $item) : $this->user()->can('create', Subject::class);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (is_string($this->name)) $merge['name'] = preg_replace('/\s+/', ' ', trim($this->name));
        if (is_string($this->code)) $merge['code'] = strtoupper(trim($this->code));
        $this->merge($merge);
    }

    public function rules(): array
    {
        $id = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects', 'name')->ignore($id)],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('subjects', 'code')->ignore($id)],
        ];
    }
}
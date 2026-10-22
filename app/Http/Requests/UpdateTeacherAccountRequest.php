<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return ($this->user()?->isAdmin() ?? false)
            && $teacher instanceof User
            && $teacher->isTeacher()
            && $teacher->teacher()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $teacher */
        $teacher = $this->route('teacher');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->id)],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers', 'employee_id')->ignore($teacher->teacher?->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}

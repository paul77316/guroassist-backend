<?php 
namespace App\Http\Requests; 
use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest {
     public function authorize(): bool {
        return $this->user()->can('create', \App\Models\Student::class);
    }

    public function rules(): array {
        return [
            'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_contact' => ['nullable', 'string', 'max:20'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'class_assignment_id' => [Rule::requiredIf(fn () => ! $this->user()->isAdmin()), 'nullable', 'integer', 'exists:class_assignments,id'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:255'],    
        ];
    }
}
<?php 
namespace App\Http\Requests; 
use Illuminate\Foundation\Http\FormRequest; 

class UpdateStudentRequest extends FormRequest {
     public function authorize(): bool { 
        return $this->user()->can('update', $this->route('student')); 
    } 
    public function rules(): array { 
        $studentId = $this->route('student')->id; 
        return [ 
            'student_id' => ['sometimes', 'string', 'max:50', 'unique:students,student_id,' . $studentId], 
            'first_name' => ['sometimes', 'string', 'max:100'], 
            'middle_name' => ['nullable', 'string', 'max:100'], 
            'last_name' => ['sometimes', 'string', 'max:100'], 
            'sex' => ['sometimes', 'in:male,female'], 
            'date_of_birth' => ['sometimes', 'date', 'before:today'], 
            'contact_number' => ['nullable', 'string', 'max:20'], 
            'guardian_name' => ['nullable', 'string', 'max:150'], 
            'guardian_contact' => ['nullable', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:255'],
            'status' => ['sometimes', 'in:active,archived,transferred'],
        ]; 
    } 
}
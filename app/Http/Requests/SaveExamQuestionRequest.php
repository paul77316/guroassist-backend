<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create and edit a question. The choice count, the correct choice and the competency are checked
 * against the exam in ExamQuestionService. The image has its own endpoint.
 */
class SaveExamQuestionRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'choices' => ['required', 'array', 'min:3', 'max:5'],
            'choices.*' => ['required', 'string', 'max:300'],
            'correct_choice' => ['required', 'string', 'in:A,B,C,D,E,a,b,c,d,e'],
            'competency_id' => ['required', 'integer'],
        ];
    }
}
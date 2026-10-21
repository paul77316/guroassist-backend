<?php

namespace App\Http\Requests;

use App\Services\ExamService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create and edit. On edit, class_assignment_id is ignored by the service. */
class SaveExamRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller and the service

    public function rules(): array
    {
        return [
            'class_assignment_id' => $this->isMethod('post') ? ['required', 'integer'] : ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'exam_date' => ['required', 'date_format:Y-m-d'],
            'choice_count' => ['required', 'integer', Rule::in(ExamService::CHOICE_COUNTS)],
        ];
    }
}
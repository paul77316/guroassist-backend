<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamScanBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_token' => ['required', 'string', 'size:40'],
            'structure_hash' => ['required', 'string', 'size:64'],
            'client_batch_id' => ['required', 'uuid'],
            'sheet_count' => ['required', 'integer', 'min:1', 'max:5000'],
            'reviewed_mark_count' => ['required', 'integer', 'min:0', 'max:250000'],
            'duplicate_check_confirmed' => ['required', 'accepted'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.question_id' => ['required', 'integer', 'distinct'],
            'questions.*.choice_counts' => ['required', 'array'],
            'questions.*.choice_counts.*' => ['required', 'integer', 'min:0', 'max:5000'],
            'questions.*.blank_count' => ['required', 'integer', 'min:0', 'max:5000'],
            'questions.*.multiple_count' => ['required', 'integer', 'min:0', 'max:5000'],
        ];
    }
}

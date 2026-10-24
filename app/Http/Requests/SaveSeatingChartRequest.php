<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSeatingChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is done in the controller via the policy
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'rows' => ['required', 'integer', 'min:1', 'max:12'],
            'cols' => ['required', 'integer', 'min:1', 'max:12'],
            'cells' => ['required', 'array', 'min:1', 'max:144'],
            'cells.*' => ['nullable', 'integer'],
        ];
    }
}

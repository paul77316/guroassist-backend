<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAttendanceSyncOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is done in the controller via the policy
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'in:keep_local,use_server'],
        ];
    }
}

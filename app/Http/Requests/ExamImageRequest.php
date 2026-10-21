<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** PNG, JPG or WebP up to 2 MB. SVG is not accepted. The service checks the real content again. */
class ExamImageRequest extends FormRequest
{
    public function authorize(): bool { return true; } // checked in the controller

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
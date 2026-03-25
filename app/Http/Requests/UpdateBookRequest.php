<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'content_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'grade_level' => ['required', 'string', 'max:50', 'exists:classes,grade_level'],
            'lesson' => ['required', 'string', 'max:100', 'exists:lessons,name'],
        ];
    }
}

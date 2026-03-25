<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('student') ?? false;
    }

    public function rules(): array
    {
        return [
            'submission_file' => ['required', 'file', 'mimes:pdf,doc,docx,zip,png,jpg,jpeg', 'max:20480'],
            'comment' => ['nullable', 'string'],
        ];
    }
}

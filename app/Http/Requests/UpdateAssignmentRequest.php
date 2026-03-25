<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'exists:classes,id'],
            'student_id' => ['nullable', 'exists:users,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['nullable', 'date'],
            'due_at' => ['required', 'date'],
            'period' => ['nullable', 'string', 'max:50'],
            'assign_scope' => ['nullable', 'in:student,class'],
            'assignment_type' => ['nullable', 'string', 'max:50'],
            'student_type' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,zip,png,jpg,jpeg', 'max:20480'],
        ];
    }
}

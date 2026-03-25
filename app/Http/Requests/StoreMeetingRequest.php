<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'exists:users,id'],
            'parent_id' => ['nullable', 'exists:users,id'],
            'meeting_at' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

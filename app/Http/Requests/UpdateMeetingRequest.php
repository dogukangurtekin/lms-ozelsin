<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'teacher']) ?? false;
    }

    public function rules(): array { return (new StoreMeetingRequest())->rules(); }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AssignmentSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'student_id' => $this->student_id,
            'submission_file_url' => $this->submission_file ? Storage::url($this->submission_file) : null,
            'comment' => $this->comment,
            'score' => $this->score,
            'teacher_feedback' => $this->teacher_feedback,
            'submitted_at' => $this->submitted_at,
        ];
    }
}

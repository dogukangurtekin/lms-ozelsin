<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Storage;

class AssignmentService
{
    public function create(array $data): Assignment
    {
        if (! empty($data['attachment'])) {
            $data['attachment'] = $data['attachment']->store('assignments/attachments', 'public');
        }

        return Assignment::create($data);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        if (! empty($data['attachment'])) {
            if ($assignment->attachment) {
                Storage::disk('public')->delete($assignment->attachment);
            }
            $data['attachment'] = $data['attachment']->store('assignments/attachments', 'public');
        }

        $assignment->update($data);
        return $assignment->refresh();
    }

    public function submit(Assignment $assignment, int $studentId, array $data): AssignmentSubmission
    {
        $path = $data['submission_file']->store('assignments/submissions', 'public');

        return AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $studentId],
            [
                'submission_file' => $path,
                'comment' => $data['comment'] ?? null,
                'submitted_at' => now(),
            ]
        );
    }

    public function grade(AssignmentSubmission $submission, array $data): AssignmentSubmission
    {
        $submission->update([
            'score' => $data['score'],
            'teacher_feedback' => $data['teacher_feedback'] ?? null,
        ]);

        return $submission->refresh();
    }
}

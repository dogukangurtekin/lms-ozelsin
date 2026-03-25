<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function create(User $user): bool { return $user->hasRole(['admin', 'teacher']); }
    public function update(User $user, Assignment $assignment): bool { return $user->hasRole('admin') || $assignment->teacher_id === $user->id; }
    public function delete(User $user, Assignment $assignment): bool { return $this->update($user, $assignment); }
}

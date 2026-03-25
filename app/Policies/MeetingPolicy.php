<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function create(User $user): bool { return $user->hasRole(['admin', 'teacher']); }
    public function update(User $user, Meeting $meeting): bool { return $user->hasRole('admin') || $meeting->teacher_id === $user->id; }
    public function delete(User $user, Meeting $meeting): bool { return $this->update($user, $meeting); }
}

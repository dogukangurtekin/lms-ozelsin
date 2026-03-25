<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function create(User $user): bool { return $user->hasRole(['admin', 'teacher']); }
    public function update(User $user, Book $book): bool { return $user->hasRole('admin') || $book->created_by === $user->id; }
    public function delete(User $user, Book $book): bool { return $this->update($user, $book); }
}

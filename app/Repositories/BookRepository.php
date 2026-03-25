<?php

namespace App\Repositories;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookRepository
{
    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Book::query()
            ->when($filters['grade_level'] ?? null, fn($q, $v) => $q->where('grade_level', $v))
            ->when($filters['lesson'] ?? null, fn($q, $v) => $q->where('lesson', $v))
            ->latest()
            ->paginate($perPage);
    }
}

<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function create(array $data): Book
    {
        if (! empty($data['cover_image'])) {
            $data['cover_image'] = $data['cover_image']->store('books/covers', 'public');
        }

        if (! empty($data['content_file'])) {
            $data['content_file'] = $data['content_file']->store('books/files', 'public');
        }

        if (! array_key_exists('content_file', $data)) {
            $data['content_file'] = '';
        }

        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        if (! empty($data['cover_image'])) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = $data['cover_image']->store('books/covers', 'public');
        }

        if (! empty($data['content_file'])) {
            if ($book->content_file) {
                Storage::disk('public')->delete($book->content_file);
            }
            $data['content_file'] = $data['content_file']->store('books/files', 'public');
        }

        $book->update($data);
        return $book->refresh();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Repositories\BookRepository;
use App\Services\BookService;

class BookController extends Controller
{
    public function index(BookRepository $repository)
    {
        return BookResource::collection(
            $repository->paginateWithFilters(request()->only(['grade_level', 'lesson']))
        );
    }

    public function store(StoreBookRequest $request, BookService $service)
    {
        $book = $service->create($request->validated() + ['created_by' => $request->user()->id]);
        return new BookResource($book);
    }

    public function show(Book $book)
    {
        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book, BookService $service)
    {
        return new BookResource($service->update($book, $request->validated()));
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json([], 204);
    }
}

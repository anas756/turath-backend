<?php

namespace App\Http\Controllers;

use App\Http\Requests\book\storeRequest;
use App\Http\Requests\book\updateRequest;
use App\Models\Book;
use App\Services\BookServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    protected $bookServices;

    public function __construct(BookServices $bookServices)
    {
        $this->bookServices = $bookServices;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $books = $this->bookServices->index();

            return response()->json([
                'success' => true,
                'data' => $books
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve books.',
                'error' => $th->getMessage() // Consider removing ->getMessage() in production for security
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(storeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $book = $this->bookServices->store($validated);

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully.',
                'data' => $book
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create book.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        try {
            // Load the relationship before returning. 
            // Note: Use ->load() on an existing instance instead of ->with()
            $book->load('categories');

            return response()->json([
                'success' => true,
                'data' => $book
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve the book.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(updateRequest $request, Book $book): JsonResponse
    {
        try {
            // Pass the model instance and validated data to your service layer
            $updatedBook = $this->bookServices->update($book, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully.',
                'data' => $updatedBook
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        try {
            // Pass the model instance to your service delete method
            $this->bookServices->delete($book);

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

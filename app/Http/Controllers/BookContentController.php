<?php

namespace App\Http\Controllers;

use App\Services\BookContentServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookContentController extends Controller
{
    protected $bookContentServices;

    public function __construct(BookContentServices $bookContentServices)
    {
        $this->bookContentServices = $bookContentServices;
    }

    /**
     * Get paginated pages for a single book.
     */
    public function getContentByBook($book_id): JsonResponse
    {
        try {
            $contents = $this->bookContentServices->getContentByBook($book_id);

            return response()->json([
                'success' => true,
                'data' => $contents
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch book contents.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for a word inside a single specific book.
     */
    public function searchInsideBook(Request $request, $book_id): JsonResponse
    {
        try {
            $keyWord = $request->query('key_word');

            // Prevent heavy empty queries
            if (empty($keyWord)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The search keyword cannot be empty.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $content = $this->bookContentServices->searchInsideBook($keyWord, $book_id);

            return response()->json([
                'success' => true,
                'count' => $content->count(),
                'results' => $content
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error while searching inside the book.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Global library search: search books using a word.
     */
    public function searchLibrer(Request $request): JsonResponse
    {
        try {
            $keyWord = $request->query('key_word');

            // Prevent heavy empty queries
            if (empty($keyWord)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The search keyword cannot be empty.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $data = $this->bookContentServices->searchLibrer($keyWord);

            return response()->json([
                'success' => true,
                'keyword' => $keyWord,
                'total_books_found' => count($data),
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Global search failed.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

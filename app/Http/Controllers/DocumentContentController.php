<?php

namespace App\Http\Controllers;

use App\Services\DocumentContentServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentContentController extends Controller
{
    protected $documentContentServices;

    public function __construct(DocumentContentServices $documentContentServices)
    {
        $this->documentContentServices = $documentContentServices;
    }

    /**
     * Get paginated pages for a single document.
     */
    public function getContentByDocument($document_id): JsonResponse
    {
        try {
            $contents = $this->documentContentServices->getContentByDocument($document_id);

            return response()->json([
                'success' => true,
                'data' => $contents
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document contents.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for a word inside a single specific document.
     */
    public function searchInsideDocument(Request $request, $document_id): JsonResponse
    {
        try {
            $keyWord = $request->query('key_word');

            if (empty($keyWord)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The search keyword cannot be empty.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $content = $this->documentContentServices->searchInsideDocument($keyWord, $document_id);

            return response()->json([
                'success' => true,
                'count' => $content->count(),
                'results' => $content
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error while searching inside the document.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Global library search: search documents using a word.
     */
    public function searchLibrary(Request $request): JsonResponse
    {
        try {
            $keyWord = $request->query('key_word');

            if (empty($keyWord)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The search keyword cannot be empty.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $data = $this->documentContentServices->searchLibrary($keyWord);

            return response()->json([
                'success' => true,
                'keyword' => $keyWord,
                'total_documents_found' => count($data),
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\document\storeRequest;
use App\Http\Requests\document\updateRequest;
use App\Models\Document; 
use App\Services\DocumentServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    protected $documentServices;

    public function __construct(DocumentServices $documentServices)
    {
        $this->documentServices = $documentServices;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $documents = $this->documentServices->index();

            return response()->json([
                'success' => true,
                'data' => $documents
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents.',
                'error' => $th->getMessage()
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
            $document = $this->documentServices->store($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Document created successfully.',
                'data' => $document
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create document.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $doc): JsonResponse
    {
        try {
            $doc->load('categorie');

            return response()->json([
                'success' => true,
                'data' => $doc
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve the document.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(updateRequest $request, Document $doc): JsonResponse
    {
        try {
            $updatedDocument = $this->documentServices->update($doc, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
                'data' => $updatedDocument
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $doc): JsonResponse
    {
        try {
            $deleted = $this->documentServices->delete($doc);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document could not be deleted.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

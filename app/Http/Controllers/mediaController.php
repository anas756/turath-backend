<?php

namespace App\Http\Controllers;

use App\Http\Requests\media\storeRequest;
use App\Http\Requests\media\UpdateRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaController extends Controller
{
    use AuthorizesRequests;
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
        $this->authorizeResource(Media::class, 'media');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'format', 'status', 'user_id', 'per_page']);
            $media = $this->mediaService->getAll($filters);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Media retrieved successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(storeRequest $request): JsonResponse
    {
        try {
            // Authorize create action
            $this->authorize('create');

            $media = $this->mediaService->store($request->validated());

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Media created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create media',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Media $media): JsonResponse
    {
        try {
            // Authorize view action
            $this->authorize('view', $media);

            // Load user relationship
            $media->load('user');

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Media retrieved successfully'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this media',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Media $media): JsonResponse
    {
        try {
            // Authorize update action using policy
            $this->authorize('update', $media);

            $updatedMedia = $this->mediaService->update($media, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedMedia,
                'message' => 'Media updated successfully'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this media',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            // Authorize delete action using policy
            $this->authorize('delete', $media);

            $this->mediaService->delete($media);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this media',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

  

    /**
     * Bulk delete media records
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'required|string'
            ]);

            // Authorize bulk delete (admin only or check each item)
            $this->authorize('bulkDelete', Media::class);

            $deleted = $this->mediaService->bulkDelete($request->ids);

            return response()->json([
                'success' => true,
                'deleted_count' => $deleted,
                'message' => "{$deleted} media records deleted successfully"
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform bulk delete',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update media status
     */
    public function updateStatus(Request $request, Media $media): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,archived,processing'
            ]);

            // Authorize update action (reuse update policy)
            $this->authorize('update', $media);

            $updatedMedia = $this->mediaService->updateStatus($media, $request->status);

            return response()->json([
                'success' => true,
                'data' => $updatedMedia,
                'message' => 'Media status updated successfully'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this media status',
                'error' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}

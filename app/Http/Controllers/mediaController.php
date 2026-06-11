<?php

namespace App\Http\Controllers;

use App\Http\Requests\media\storeRequest;
use App\Http\Requests\media\UpdateRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    use AuthorizesRequests;
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'format', 'status', 'user_id', 'per_page']);
            $media   = $this->mediaService->getAll($filters);

            return response()->json([
                'success' => true,
                'data'    => $media,
                'message' => 'Media retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur Index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(storeRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Media::class);

            $media = $this->mediaService->store($request->validated());

            return response()->json([
                'success' => true,
                'data'    => $media,
                'message' => 'Media created successfully',
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create media',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur Store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create media',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Media $media): JsonResponse
    {
        try {
            $this->authorize('view', $media);

            $media->load('user');

            return response()->json([
                'success' => true,
                'data'    => $media,
                'message' => 'Media retrieved successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this media',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur Show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateRequest $request, Media $media): JsonResponse
    {
        try {
            $this->authorize('update', $media);

            $updatedMedia = $this->mediaService->update($media, $request->validated());

            return response()->json([
                'success' => true,
                'data'    => $updatedMedia,
                'message' => 'Media updated successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this media',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur Update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        try {
            $this->authorize('delete', $media);

            $this->mediaService->delete($media);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this media',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur Suppression: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids'   => 'required|array',
                'ids.*' => 'required|string',
            ]);

            $this->authorize('bulkDelete', Media::class);

            $deleted = $this->mediaService->bulkDelete($request->ids);

            return response()->json([
                'success'       => true,
                'deleted_count' => $deleted,
                'message'       => "{$deleted} media records deleted successfully",
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform bulk delete',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur BulkDelete: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, Media $media): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,archived,processing',
            ]);

            $this->authorize('update', $media);

            $updatedMedia = $this->mediaService->updateStatus($media, $request->status);

            return response()->json([
                'success' => true,
                'data'    => $updatedMedia,
                'message' => 'Media status updated successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this media status',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur UpdateStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

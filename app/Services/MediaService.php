<?php

namespace App\Services;

use App\Models\Media;
use Exception;
use Illuminate\Support\Facades\Log;

class MediaService
{
    /**
     * Store a new media record
     */
    public function store(array $data)
    {
        try {
            // Add user_id if authenticated
            if (auth()->check() && !isset($data['user_id'])) {
                $data['user_id'] = auth()->id();
            }

            // Set curator from authenticated user if not provided
            if (!isset($data['curator']) && auth()->check()) {
                $data['curator'] = auth()->user()->name;
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'active';
            }

            $media = Media::create($data);

            Log::info('Media created successfully', [
                'media_id' => $media->id,
                'title' => $media->title,
                'user_id' => $media->user_id
            ]);

            return $media;
        } catch (\Exception $e) {
            Log::error('Failed to create media: ' . $e->getMessage());
            throw new Exception('Failed to create media: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing media record
     */
    public function update(Media $media, array $data)
    {
        try {
            // Check if user is authorized to update
            if (!$this->isAuthorized($media)) {
                throw new Exception('You are not authorized to update this media.');
            }

            $media->update($data);

            Log::info('Media updated successfully', [
                'media_id' => $media->id,
                'title' => $media->title,
                'user_id' => $media->user_id
            ]);

            return $media->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update media: ' . $e->getMessage());
            throw new Exception('Failed to update media: ' . $e->getMessage());
        }
    }

    /**
     * Delete a media record
     */
    public function delete(Media $media)
    {
        try {
            // Check if user is authorized to delete
            if (!$this->isAuthorized($media)) {
                throw new Exception('You are not authorized to delete this media.');
            }

            $mediaData = [
                'id' => $media->id,
                'title' => $media->title
            ];

            $media->delete();

            Log::info('Media deleted successfully', $mediaData);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete media: ' . $e->getMessage());
            throw new Exception('Failed to delete media: ' . $e->getMessage());
        }
    }

    /**
     * Get all media with optional filters
     */
    public function getAll(array $filters = [])
    {
        try {
            $query = Media::query();

            // Apply filters
            if (isset($filters['type'])) {
                $query->byType($filters['type']);
            }

            if (isset($filters['format'])) {
                $query->byFormat($filters['format']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['user_id'])) {
                $query->byUser($filters['user_id']);
            }

            // Order by latest first
            $query->orderBy('date_added', 'desc');

            // Pagination
            $perPage = $filters['per_page'] ?? 15;

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve media: ' . $e->getMessage());
            throw new Exception('Failed to retrieve media: ' . $e->getMessage());
        }
    }

    /**
     * Get a single media record
     */
    public function getById($id)
    {
        try {
            $media = Media::findOrFail($id);
            return $media;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve media: ' . $e->getMessage());
            throw new Exception('Media not found.');
        }
    }

    /**
     * Check if user is authorized to modify the media
     */
    private function isAuthorized(Media $media)
    {
        // Admin can do anything
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return true;
        }

        // User can only modify their own media
        return auth()->id() === $media->user_id;
    }

    /**
     * Bulk delete media records
     */
    public function bulkDelete(array $ids)
    {
        try {
            $deleted = Media::whereIn('id', $ids)
                ->where('user_id', auth()->id())
                ->delete();

            Log::info('Bulk delete completed', [
                'deleted_count' => $deleted,
                'user_id' => auth()->id()
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete media: ' . $e->getMessage());
            throw new Exception('Failed to delete media records.');
        }
    }

    /**
     * Update media status
     */
    public function updateStatus(Media $media, string $status)
    {
        try {
            if (!$this->isAuthorized($media)) {
                throw new Exception('You are not authorized to update this media.');
            }

            $media->update(['status' => $status]);

            Log::info('Media status updated', [
                'media_id' => $media->id,
                'old_status' => $media->getOriginal('status'),
                'new_status' => $status
            ]);

            return $media;
        } catch (\Exception $e) {
            Log::error('Failed to update media status: ' . $e->getMessage());
            throw new Exception('Failed to update media status.');
        }
    }
}

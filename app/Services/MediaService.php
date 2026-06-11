<?php

namespace App\Services;

use App\Models\Media;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Store a new media record
     */
    public function store(array $data)
    {
        try {
            // Always set from auth — never trust frontend
            $data['user_id'] = auth()->id();

            if (empty($data['curator'])) {
                $data['curator'] = auth()->user()->name;
            }

            $data['status'] = $data['status'] ?? 'active';

            if (isset($data['file_path']) && $data['file_path'] instanceof UploadedFile) {
                $data = array_merge($data, $this->processFile($data['file_path']));
            }

            $data['date_added'] = now();

            $media = Media::create($data);

            Log::info('Media created', [
                'media_id' => $media->id,
                'title'    => $media->title,
                'format'   => $media->format,
                'size'     => $media->size,
                'user_id'  => $media->user_id,
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
            if (isset($data['file_path']) && $data['file_path'] instanceof UploadedFile) {
                // Delete old file first
                if ($media->file_path) {
                    Storage::disk('public')->delete($media->file_path);
                }
                $data = array_merge($data, $this->processFile($data['file_path']));
            }

            $media->update($data);

            Log::info('Media updated', [
                'media_id' => $media->id,
                'title'    => $media->title,
                'user_id'  => $media->user_id,
            ]);

            return $media->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update media: ' . $e->getMessage());
            throw new Exception('Failed to update media: ' . $e->getMessage());
        }
    }

    /**
     * Delete a media record and its physical file
     */
    public function delete(Media $media)
    {
        try {
            if ($media->file_path) {
                Storage::disk('public')->delete($media->file_path);
            }

            $mediaData = ['id' => $media->id, 'title' => $media->title];
            $media->delete();

            Log::info('Media deleted', $mediaData);

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

            if (!empty($filters['type'])) {
                $query->byType($filters['type']);
            }
            if (!empty($filters['format'])) {
                $query->byFormat($filters['format']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $query->byUser($filters['user_id']);
            }

            $query->orderBy('date_added', 'desc');

            $perPage = $filters['per_page'] ?? 15;

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve media: ' . $e->getMessage());
            throw new Exception('Failed to retrieve media: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete media records and their files
     */
    public function bulkDelete(array $ids)
    {
        try {
            $mediaItems = Media::whereIn('id', $ids)->get();

            foreach ($mediaItems as $media) {
                if ($media->file_path) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }

            $deleted = Media::whereIn('id', $ids)->delete();

            Log::info('Bulk delete completed', [
                'deleted_count' => $deleted,
                'user_id'       => auth()->id(),
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
            $oldStatus = $media->status;
            $media->update(['status' => $status]);

            Log::info('Media status updated', [
                'media_id'   => $media->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            return $media->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update media status: ' . $e->getMessage());
            throw new Exception('Failed to update media status.');
        }
    }

    /**
     * Process uploaded file — store it and extract all metadata
     */
    private function processFile(UploadedFile $file): array
    {
        $path   = $file->store('media', 'public');
        $mime   = $file->getMimeType();
        $format = strtoupper($file->getClientOriginalExtension());
        $size   = $file->getSize();

        return [
            'file_path'  => $path,
            'format'     => $format,
            'size'       => $size,
            'resolution' => $this->extractResolution($file, $mime),
        ];
    }

    /**
     * Extract resolution from image or video
     */
    private function extractResolution(UploadedFile $file, string $mime): ?string
    {
        if (str_starts_with($mime, 'image/')) {
            $dimensions = @getimagesize($file->getPathname());
            if ($dimensions && $dimensions[0] && $dimensions[1]) {
                return $dimensions[0] . 'x' . $dimensions[1];
            }
        }

        if (str_starts_with($mime, 'video/')) {
            return $this->extractVideoResolution($file->getPathname());
        }

        return null;
    }

    /**
     * Extract video resolution via ffprobe
     */
    private function extractVideoResolution(string $path): ?string
    {
        try {
            $cmd    = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 " . escapeshellarg($path);
            $output = shell_exec($cmd);

            if ($output) {
                $parts = explode(',', trim($output));
                if (count($parts) === 2 && $parts[0] && $parts[1]) {
                    return trim($parts[0]) . 'x' . trim($parts[1]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('ffprobe failed: ' . $e->getMessage());
        }

        return null;
    }
}

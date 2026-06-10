<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Determine if the user can view any media.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view media list
    }

    /**
     * Determine if the user can view the media.
     */
    public function view(User $user, Media $media): bool
    {
        return true; // Allow viewing for all authenticated users
    }

    /**
     * Determine if the user can create media.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create media
    }

    /**
     * Determine if the user can update the media.
     */
    public function update(User $user, Media $media): bool
    {
        // User can update if they own the media OR are admin
        return $user->id === $media->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        // User can delete if they own the media OR are admin
        return $user->id === $media->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can restore the media.
     */
    public function restore(User $user, Media $media): bool
    {
        // Only admin can restore soft-deleted media
        return $user->isAdmin();
    }

    /**
     * Determine if the user can permanently delete the media.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        // Only admin can force delete
        return $user->isAdmin();
    }

    /**
     * Determine if the user can perform bulk delete.
     */
    public function bulkDelete(User $user): bool
    {
        // Only admin can bulk delete
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view statistics.
     */
    public function viewStats(User $user): bool
    {
        // Only admin can view statistics
        return $user->isAdmin();
    }
}

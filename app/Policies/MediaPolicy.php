<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return  $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return  $user->isAdmin();
    }

    public function restore(User $user, Media $media): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Media $media): bool
    {
        return $user->isAdmin();
    }

    public function bulkDelete(User $user): bool
    {
        return $user->isAdmin();
    }
}

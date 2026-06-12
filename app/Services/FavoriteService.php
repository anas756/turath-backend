<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Favorite;
use App\Models\Media;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FavoriteService
{
    // -------------------------------------------------------------------------
    // Supported favorable types — extend here when adding new favoritable models
    // -------------------------------------------------------------------------
    private const ALLOWED_TYPES = [
        'document' => Document::class,
        'media'    => Media::class,
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Toggle a favorite on/off.
     * Returns ['action' => 'added'|'removed', 'favorite' => Favorite|null]
     */
    public function toggle(string $type, string $favorableId, string $userId): array
    {
        $modelClass = $this->resolveType($type);

        $existing = Favorite::query()
            ->forUser($userId)
            ->where('favorable_id', $favorableId)
            ->where('favorable_type', $modelClass)
            ->first();

        if ($existing) {
            $existing->delete();
            return ['action' => 'removed', 'favorite' => null];
        }

        $favorite = Favorite::create([
            'user_id'        => $userId,
            'favorable_id'   => $favorableId,
            'favorable_type' => $modelClass,
        ]);

        return ['action' => 'added', 'favorite' => $favorite];
    }

    /**
     * Add a favorite (idempotent — won't duplicate).
     */
    public function add(string $type, string $favorableId, string $userId): Favorite
    {
        $modelClass = $this->resolveType($type);

        return Favorite::firstOrCreate([
            'user_id'        => $userId,
            'favorable_id'   => $favorableId,
            'favorable_type' => $modelClass,
        ]);
    }

    /**
     * Remove a favorite.
     */
    public function remove(string $type, string $favorableId, string $userId): bool
    {
        $modelClass = $this->resolveType($type);

        return (bool) Favorite::query()
            ->forUser($userId)
            ->where('favorable_id', $favorableId)
            ->where('favorable_type', $modelClass)
            ->delete();
    }

    /**
     * Check whether a user has favorited a specific item.
     */
    public function isFavorited(string $type, string $favorableId, string $userId): bool
    {
        $modelClass = $this->resolveType($type);

        return Favorite::query()
            ->forUser($userId)
            ->where('favorable_id', $favorableId)
            ->where('favorable_type', $modelClass)
            ->exists();
    }

    /**
     * Get all favorites for a user, optionally filtered by type, paginated.
     */
    public function getUserFavorites(
        string $userId,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Favorite::query()->forUser($userId)->latest();

        if ($type) {
            $query->ofType($this->resolveType($type));
        }

        return $query->paginate($perPage);
    }

    /**
     * Get favorited Documents for a user (with eager-loaded document data).
     */
    public function getUserDocumentFavorites(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Favorite::query()
            ->forUser($userId)
            ->documents()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get favorited Media for a user (with eager-loaded media data).
     */
    public function getUserMediaFavorites(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Favorite::query()
            ->forUser($userId)
            ->media()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Count favorites per type for a user.
     * Returns ['document' => int, 'media' => int, 'total' => int]
     */
    public function getUserFavoritesCounts(string $userId): array
    {
        $counts = Favorite::query()
            ->forUser($userId)
            ->get(['favorable_type'])
            ->groupBy('favorable_type')
            ->map(fn($group) => $group->count());

        $documentCount = $counts[Document::class] ?? 0;
        $mediaCount    = $counts[Media::class] ?? 0;

        return [
            'document' => $documentCount,
            'media'    => $mediaCount,
            'total'    => $documentCount + $mediaCount,
        ];
    }

    /**
     * Given a collection of item IDs, return which ones are favorited by the user.
     * Useful for bulk-flagging list pages.
     */
    public function getFavoritedIds(string $type, array $ids, string $userId): Collection
    {
        $modelClass = $this->resolveType($type);

        return Favorite::query()
            ->forUser($userId)
            ->where('favorable_type', $modelClass)
            ->whereIn('favorable_id', $ids)
            ->pluck('favorable_id');
    }

    /**
     * Remove all favorites for a user (e.g. account deletion).
     */
    public function clearUserFavorites(string $userId): int
    {
        return Favorite::query()->forUser($userId)->delete();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Resolve a short type alias ('document', 'media') to its model class.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveType(string $type): string
    {
        $normalized = strtolower($type);

        if (!array_key_exists($normalized, self::ALLOWED_TYPES)) {
            throw new \InvalidArgumentException(
                "Unsupported favorable type [{$type}]. Allowed: " .
                implode(', ', array_keys(self::ALLOWED_TYPES))
            );
        }

        return self::ALLOWED_TYPES[$normalized];
    }
}

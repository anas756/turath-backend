<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Favorite;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    private const TYPES = [
        'document' => Document::class,
        'media'    => Media::class,
    ];

    // GET /favorites
    // Returns documents list, media list, and counts all in one shot
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $perPage = (int) $request->input('per_page', 15);

        $documents = Favorite::where('user_id', $userId)
            ->where('favorable_type', Document::class)
            ->latest()
            ->paginate($perPage, ['*'], 'documents_page');

        $media = Favorite::where('user_id', $userId)
            ->where('favorable_type', Media::class)
            ->latest()
            ->paginate($perPage, ['*'], 'media_page');

        $documentCount = Favorite::where('user_id', $userId)
            ->where('favorable_type', Document::class)
            ->count();

        $mediaCount = Favorite::where('user_id', $userId)
            ->where('favorable_type', Media::class)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'documents' => $documents,
                'media'     => $media,
                'counts'    => [
                    'document' => $documentCount,
                    'media'    => $mediaCount,
                    'total'    => $documentCount + $mediaCount,
                ],
            ],
        ]);
    }

    // POST /favorites/document  — body: { favorable_id }
    public function storeDocument(Request $request): JsonResponse
    {
        $request->validate(['favorable_id' => ['required', 'string']]);

        $favorite = Favorite::firstOrCreate([
            'user_id'        => $request->user()->id,
            'favorable_id'   => $request->input('favorable_id'),
            'favorable_type' => Document::class,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Document added to favorites.',
            'favorite' => $favorite,
        ], 201);
    }

    // POST /favorites/media  — body: { favorable_id }
    public function storeMedia(Request $request): JsonResponse
    {
        $request->validate(['favorable_id' => ['required', 'string']]);

        $favorite = Favorite::firstOrCreate([
            'user_id'        => $request->user()->id,
            'favorable_id'   => $request->input('favorable_id'),
            'favorable_type' => Media::class,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Media added to favorites.',
            'favorite' => $favorite,
        ], 201);
    }

    // DELETE /favorites/{type}/{favorable_id}  — type: document|media
    public function destroy(Request $request, string $type, string $favorableId): JsonResponse
    {
        if (! array_key_exists($type, self::TYPES)) {
            return response()->json(['success' => false, 'message' => 'Invalid type.'], 422);
        }

        $deleted = Favorite::where('user_id', $request->user()->id)
            ->where('favorable_id',   $favorableId)
            ->where('favorable_type', self::TYPES[$type])
            ->delete();

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => 'Favorite not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Removed from favorites.']);
    }
}

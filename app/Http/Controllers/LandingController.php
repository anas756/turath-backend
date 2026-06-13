<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Media;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    // GET /landing/preview  — public, no auth required
    public function preview(): JsonResponse
    {
        $documents = Document::latest()->limit(3)->get();

        $featuredVideo = Media::active()
            ->where('type', 'video')
            ->latest()
            ->first();

        $media = Media::active()
            ->where('type', '!=', 'collection')
            ->latest()
            ->limit($featuredVideo ? 6 : 5)
            ->get();

        if ($featuredVideo) {
            $media = $media
                ->reject(fn ($item) => (string) $item->getKey() === (string) $featuredVideo->getKey())
                ->prepend($featuredVideo)
                ->take(5)
                ->values();
        }

        $collections = [
            'categories' => Categorie::with('documents')
                ->latest()
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'documents'   => $documents,
                'media'       => $media,
                'collections' => $collections,
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

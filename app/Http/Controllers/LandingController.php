<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    // GET /landing/preview  — public, no auth required
    public function preview(): JsonResponse
    {
        $documents = Document::latest()->limit(3)->get();

        $media = Media::where('type', '!=', 'collection')
            ->latest()
            ->limit(3)
            ->get();

        $collections = [
            'documents' => Document::latest()->limit(2)->get(),
            'media'     => Media::where('type', '!=', 'collection')->latest()->limit(2)->get(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'documents'   => $documents,
                'media'       => $media,
                'collections' => $collections,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ImportOpenLibraryBooksJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpenLibrarySyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subjects' => ['nullable', 'array', 'min:1'],
            'subjects.*' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'ebooks_only' => ['nullable', 'boolean'],
        ]);

        $subjects = $validated['subjects'] ?? [];
        $limit = $validated['limit'] ?? null;
        $ebooksOnly = $request->has('ebooks_only')
            ? $request->boolean('ebooks_only')
            : (bool) config('services.open_library.sync_ebooks_only', false);

        ImportOpenLibraryBooksJob::dispatch($subjects, $limit, $ebooksOnly)
            ->onConnection(config('services.open_library.queue_connection', 'background'))
            ->onQueue(config('services.open_library.queue', 'open-library'));

        return response()->json([
            'success' => true,
            'message' => 'Open Library sync queued in the background.',
            'data' => [
                'subjects' => count($subjects) > 0
                    ? $subjects
                    : explode(',', (string) config('services.open_library.sync_subjects', 'history,literature')),
                'limit' => $limit ?? (int) config('services.open_library.sync_limit', 20),
                'ebooks_only' => $ebooksOnly,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}

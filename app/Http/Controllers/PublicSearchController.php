<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentContent;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $type = $this->normalizeType((string) $request->query('type', 'all'));
        $limit = min(max((int) $request->query('limit', 12), 1), 24);

        if ($query === '') {
            $query = 'Moroccan heritage';
        }

        $documents = in_array($type, ['all', 'archive'], true)
            ? $this->searchDocuments($query, $limit)
            : collect();

        $media = in_array($type, ['all', 'watch'], true)
            ? $this->searchLocalMedia($query, $limit)
            : collect();

        $youtube = in_array($type, ['all', 'watch'], true)
            ? $this->searchYoutube($query, 8)
            : [
                'configured' => false,
                'items' => [],
                'message' => null,
            ];

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'type' => $type,
                'internal' => [
                    'documents' => $documents->values(),
                    'media' => $media->values(),
                ],
                'external' => [
                    'youtube' => $youtube['items'],
                    'youtube_configured' => $youtube['configured'],
                    'youtube_message' => $youtube['message'],
                ],
                'counts' => [
                    'internal' => $documents->count() + $media->count(),
                    'documents' => $documents->count(),
                    'media' => $media->count(),
                    'external' => count($youtube['items']),
                    'total' => $documents->count() + $media->count() + count($youtube['items']),
                ],
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function searchDocuments(string $query, int $limit): Collection
    {
        $contentMatches = DocumentContent::where('content', 'like', '%' . $query . '%')
            ->orderBy('page_number')
            ->limit(80)
            ->get()
            ->groupBy('document_id')
            ->map(fn (Collection $pages) => $this->snippet($pages->first()->content, $query));

        return Document::with('categorie')
            ->latest()
            ->limit(250)
            ->get()
            ->filter(fn (Document $document) => $this->matchesDocument($document, $query) || $contentMatches->has((string) $document->getKey()))
            ->take($limit)
            ->map(fn (Document $document) => [
                'id' => (string) $document->getKey(),
                'title' => $document->title,
                'description' => Str::limit((string) $document->description, 180),
                'authors' => $this->listValue($document->authors),
                'tags' => $this->listValue($document->tags),
                'cover' => $document->cover,
                'source' => $document->source,
                'category' => $document->categorie?->name,
                'match' => $contentMatches->get((string) $document->getKey()),
                'result_type' => 'document',
            ]);
    }

    private function searchLocalMedia(string $query, int $limit): Collection
    {
        return Media::active()
            ->latest()
            ->limit(250)
            ->get()
            ->filter(fn (Media $media) => $this->matchesMedia($media, $query))
            ->sortByDesc(fn (Media $media) => strtolower((string) $media->type) === 'video' ? 1 : 0)
            ->take($limit)
            ->map(fn (Media $media) => [
                'id' => (string) $media->getKey(),
                'title' => $media->title,
                'type' => $media->type,
                'format' => $media->format,
                'curator' => $media->curator,
                'description' => Str::limit((string) $media->description, 180),
                'tags' => $this->listValue($media->tags),
                'file_path' => $media->file_path,
                'resolution' => $media->resolution,
                'date_added' => $media->date_added,
                'result_type' => 'media',
            ]);
    }

    private function searchYoutube(string $query, int $limit): array
    {
        $apiKey = config('services.youtube.api_key');

        if (!$apiKey) {
            return [
                'configured' => false,
                'items' => [],
                'message' => 'Add YOUTUBE_API_KEY to enable external YouTube results.',
            ];
        }

        try {
            $response = Http::baseUrl(config('services.youtube.base_url', 'https://www.googleapis.com/youtube/v3'))
                ->acceptJson()
                ->timeout(10)
                ->get('/search', [
                    'key' => $apiKey,
                    'part' => 'snippet',
                    'type' => 'video',
                    'maxResults' => $limit,
                    'q' => trim($query . ' Moroccan heritage patrimoine marocain التراث المغربي'),
                    'safeSearch' => 'moderate',
                    'videoEmbeddable' => 'true',
                ]);

            if (!$response->successful()) {
                Log::warning('YouTube search failed.', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 240),
                ]);

                return [
                    'configured' => true,
                    'items' => [],
                    'message' => 'External YouTube results are temporarily unavailable.',
                ];
            }

            return [
                'configured' => true,
                'message' => null,
                'items' => collect($response->json('items') ?? [])
                    ->map(function (array $item) {
                        $videoId = $item['id']['videoId'] ?? null;
                        $snippet = $item['snippet'] ?? [];

                        if (!$videoId) {
                            return null;
                        }

                        return [
                            'id' => $videoId,
                            'title' => $snippet['title'] ?? 'YouTube video',
                            'description' => Str::limit((string) ($snippet['description'] ?? ''), 180),
                            'channel' => $snippet['channelTitle'] ?? 'YouTube',
                            'published_at' => $snippet['publishedAt'] ?? null,
                            'thumbnail' => $snippet['thumbnails']['high']['url']
                                ?? $snippet['thumbnails']['medium']['url']
                                ?? $snippet['thumbnails']['default']['url']
                                ?? null,
                            'url' => 'https://www.youtube.com/watch?v=' . $videoId,
                            'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
                            'result_type' => 'youtube',
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('YouTube search request failed.', ['error' => $exception->getMessage()]);

            return [
                'configured' => true,
                'items' => [],
                'message' => 'External YouTube results are temporarily unavailable.',
            ];
        }
    }

    private function matchesDocument(Document $document, string $query): bool
    {
        return $this->contains($document->title, $query)
            || $this->contains($document->description, $query)
            || $this->contains($document->source, $query)
            || $this->contains($document->categorie?->name, $query)
            || $this->containsList($document->authors, $query)
            || $this->containsList($document->tags, $query);
    }

    private function matchesMedia(Media $media, string $query): bool
    {
        return $this->contains($media->title, $query)
            || $this->contains($media->type, $query)
            || $this->contains($media->format, $query)
            || $this->contains($media->curator, $query)
            || $this->contains($media->description, $query)
            || $this->containsList($media->tags, $query);
    }

    private function contains(mixed $value, string $query): bool
    {
        return is_string($value) && Str::contains(Str::lower($value), Str::lower($query));
    }

    private function containsList(mixed $value, string $query): bool
    {
        return collect($this->listValue($value))
            ->contains(fn (string $item) => $this->contains($item, $query));
    }

    private function listValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    private function snippet(string $content, string $query): string
    {
        $position = stripos($content, $query);
        $start = $position === false ? 0 : max(0, $position - 70);

        return Str::limit(trim(substr($content, $start, 240)), 220);
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'document', 'documents', 'archive' => 'archive',
            'video', 'videos', 'media', 'watch' => 'watch',
            default => 'all',
        };
    }
}

<?php

namespace App\Services;

use App\Models\Categorie;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OpenLibraryImportService
{
    /**
     * Synchronize books from Open Library subjects into local documents.
     */
    public function sync(array $subjects = [], ?int $limit = null, bool $ebooksOnly = false): array
    {
        $subjects = $this->normalizeSubjects($subjects);
        $limit = $this->normalizeLimit($limit ?? (int) config('services.open_library.sync_limit', 20));

        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'subjects' => [],
        ];

        foreach ($subjects as $subject) {
            $subjectSummary = $this->syncSubject($subject, $limit, $ebooksOnly);

            foreach (['created', 'updated', 'skipped', 'failed'] as $key) {
                $summary[$key] += $subjectSummary[$key];
            }

            $summary['subjects'][] = $subjectSummary;
            $this->waitBetweenRequests();
        }

        return $summary;
    }

    private function syncSubject(string $subject, int $limit, bool $ebooksOnly): array
    {
        $payload = $this->fetchSubject($subject, $limit, $ebooksOnly);
        $category = $this->findOrCreateCategory($this->displaySubjectName($subject));
        $works = $payload['docs'] ?? [];

        if ($ebooksOnly) {
            $works = collect($works)
                ->filter(fn (array $work) => ($work['has_fulltext'] ?? false) === true)
                ->values()
                ->all();
        }

        $summary = [
            'subject' => $subject,
            'category_id' => (string) $category->getKey(),
            'category_name' => $category->name,
            'total' => count($works),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($works as $work) {
            try {
                $documentData = $this->mapWorkToDocument($work, $category);

                if ($documentData === null) {
                    $summary['skipped']++;
                    continue;
                }

                $document = Document::updateOrCreate(
                    ['open_library_key' => $documentData['open_library_key']],
                    $documentData
                );

                $document->wasRecentlyCreated
                    ? $summary['created']++
                    : $summary['updated']++;
            } catch (Throwable $exception) {
                $summary['failed']++;

                Log::warning('Failed importing Open Library work.', [
                    'subject' => $subject,
                    'work_key' => $work['key'] ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function fetchSubject(string $subject, int $limit, bool $ebooksOnly): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withUserAgent($this->userAgent())
            ->retry(3, 1000)
            ->timeout(20)
            ->get('/search.json', array_filter([
                'subject' => $this->subjectKey($subject),
                'limit' => $limit,
                'fields' => implode(',', [
                    'key',
                    'title',
                    'author_name',
                    'cover_i',
                    'cover_edition_key',
                    'subject',
                    'first_publish_year',
                    'edition_count',
                    'has_fulltext',
                ]),
            ]));

        $response->throw();

        return $response->json();
    }

    private function findOrCreateCategory(string $name): Categorie
    {
        $name = trim($name) !== '' ? trim($name) : 'Open Library';
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'open-library-' . substr(md5($name), 0, 8);
        }

        $category = Categorie::where('slug', $slug)->first();

        if ($category) {
            return $category;
        }

        return Categorie::firstOrCreate(
            ['name' => $name],
            [
                'description' => "Books imported from Open Library subject: {$name}",
                'slug' => $slug,
                'icon' => 'book-open',
            ]
        );
    }

    private function mapWorkToDocument(array $work, Categorie $category): ?array
    {
        $key = $work['key'] ?? null;
        $title = trim((string) ($work['title'] ?? ''));

        if (!$key || $title === '') {
            return null;
        }

        return [
            'title' => $title,
            'description' => $this->description($work, $category->name),
            'authors' => $this->authors($work),
            'cover' => $this->coverUrl($work),
            'file_path' => null,
            'source' => 'open_library',
            'open_library_key' => $key,
            'categorie_id' => (string) $category->getKey(),
            'tags' => $this->tags($work, $category->name),
        ];
    }

    private function description(array $work, string $categoryName): string
    {
        $parts = ["Imported from Open Library category: {$categoryName}."];

        if (!empty($work['first_publish_year'])) {
            $parts[] = 'First published in ' . $work['first_publish_year'] . '.';
        }

        if (!empty($work['edition_count'])) {
            $parts[] = $work['edition_count'] . ' editions listed on Open Library.';
        }

        return implode(' ', $parts);
    }

    private function authors(array $work): array
    {
        if (isset($work['author_name']) && is_array($work['author_name'])) {
            $authors = $work['author_name'];
        } else {
            $authors = collect($work['authors'] ?? [])
                ->map(fn (array $author) => $author['name'] ?? null)
                ->all();
        }

        $authors = collect($authors)
            ->filter()
            ->values()
            ->all();

        return count($authors) > 0 ? $authors : ['Unknown'];
    }

    private function coverUrl(array $work): ?string
    {
        if (!empty($work['cover_i'])) {
            return 'https://covers.openlibrary.org/b/id/' . $work['cover_i'] . '-L.jpg';
        }

        if (!empty($work['cover_id'])) {
            return 'https://covers.openlibrary.org/b/id/' . $work['cover_id'] . '-L.jpg';
        }

        if (!empty($work['cover_edition_key'])) {
            return 'https://covers.openlibrary.org/b/olid/' . $work['cover_edition_key'] . '-L.jpg';
        }

        return null;
    }

    private function tags(array $work, string $categoryName): array
    {
        $subjects = $work['subjects'] ?? $work['subject'] ?? [];

        if (is_string($subjects)) {
            $subjects = [$subjects];
        }

        return collect(array_merge(['open-library', $categoryName], $subjects))
            ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn (string $tag) => Str::limit(trim($tag), 50, ''))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function normalizeSubjects(array $subjects): array
    {
        $subjects = count($subjects) > 0
            ? $subjects
            : explode(',', (string) config('services.open_library.sync_subjects', 'history,literature'));

        return collect($subjects)
            ->map(fn ($subject) => trim((string) $subject))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeLimit(int $limit): int
    {
        return max(1, min($limit, 100));
    }

    private function subjectKey(string $subject): string
    {
        $subject = Str::of($subject)
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->toString();

        return trim((string) preg_replace('/[^a-z0-9:_]+/', '_', $subject), '_');
    }

    private function displaySubjectName(string $subject): string
    {
        return Str::of($subject)
            ->replace(['_', '-'], ' ')
            ->trim()
            ->toString();
    }

    private function waitBetweenRequests(): void
    {
        usleep((int) config('services.open_library.request_delay_microseconds', 350000));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.open_library.base_url', 'https://openlibrary.org'), '/');
    }

    private function userAgent(): string
    {
        return (string) config('services.open_library.user_agent', 'TurathBackend (configure OPEN_LIBRARY_USER_AGENT)');
    }
}

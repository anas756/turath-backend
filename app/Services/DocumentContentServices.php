<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentContent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class DocumentContentServices
{
    private const MIN_TEXT_LENGTH = 20;
    private const OPEN_LIBRARY_CHUNK_LENGTH = 8000;
    private const OPEN_LIBRARY_EDITIONS_LIMIT = 5;
    private const INTERNET_ARCHIVE_ATTEMPTS_LIMIT = 2;

    // -------------------------------------------------------------------------
    // Search & Retrieval
    // -------------------------------------------------------------------------

    public function getContentByDocument(string $document_id)
    {
        return DocumentContent::where('document_id', $document_id)
            ->orderBy('page_number', 'asc')
            ->paginate(1);
    }

    public function searchInsideDocument(string $keyWord, string $document_id)
    {
        return DocumentContent::where('document_id', $document_id)
            ->where('content', 'like', '%' . $keyWord . '%')
            ->orderBy('page_number', 'asc')
            ->get(['page_number', 'content']);
    }

    public function searchLibrary(string $keyWord): array
    {
        return DocumentContent::where('content', 'like', '%' . $keyWord . '%')
            ->with('document')
            ->get()
            ->groupBy('document_id')
            ->map(function ($pages) {
                $document = $pages->first()->document;

                return [
                    'document_id' => $document?->id,
                    'title'       => $document?->title,
                    'cover_url'   => $document?->cover,
                    'matches'     => $pages->map(fn ($page) => [
                        'page_number' => $page->page_number,
                        'snippet'     => mb_strimwidth($page->content, 0, 120, '...'),
                    ]),
                ];
            })
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Smart extraction pipeline
    // -------------------------------------------------------------------------

    public function processAllMissing(?int $limit = null): array
    {
        $summary = [
            'checked' => 0,
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'no_source' => 0,
            'results' => [],
        ];

        $query = Document::query()->latest();

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        foreach ($query->get() as $document) {
            $summary['checked']++;
            $result = $this->processDocument($document);
            $summary['results'][] = $result;

            match ($result['status']) {
                'skipped' => $summary['skipped']++,
                'processed' => $summary['processed']++,
                'no_source' => $summary['no_source']++,
                default => $summary['failed']++,
            };
        }

        return $summary;
    }

    public function processDocument(Document $document): array
    {
        $documentId = (string) $document->getKey();

        if ($this->hasContent($document)) {
            return [
                'document_id' => $documentId,
                'title' => $document->title,
                'status' => 'skipped',
                'source' => 'existing_content',
                'message' => 'Document content already exists.',
            ];
        }

        $openLibraryKey = $this->openLibraryKey($document);

        if ($openLibraryKey) {
            try {
                $pages = $this->fetchOpenLibraryPages($openLibraryKey);

                if (count($pages) > 0) {
                    return [
                        'document_id' => $documentId,
                        'title' => $document->title,
                        'status' => 'processed',
                        'source' => 'open_library',
                        'pages_stored' => $this->persistPages($document, $pages),
                    ];
                }
            } catch (\Throwable $exception) {
                Log::warning('Open Library content extraction failed.', [
                    'document_id' => $documentId,
                    'open_library_key' => $openLibraryKey,
                    'error' => $exception->getMessage(),
                ]);
            }

            return [
                'document_id' => $documentId,
                'title' => $document->title,
                'status' => 'failed',
                'source' => 'open_library',
                'message' => 'No usable Open Library content could be retrieved.',
            ];
        }

        if (!$document->file_path) {
            return [
                'document_id' => $documentId,
                'title' => $document->title,
                'status' => 'no_source',
                'source' => null,
                'message' => 'Document has no Open Library key and no local file.',
            ];
        }

        try {
            $stored = $this->extractAndStoreLocalFile($document);

            return [
                'document_id' => $documentId,
                'title' => $document->title,
                'status' => $stored > 0 ? 'processed' : 'failed',
                'source' => 'local_file',
                'pages_stored' => $stored,
                'message' => $stored > 0 ? null : 'Local file produced no text.',
            ];
        } catch (\Throwable $exception) {
            Log::error('Local document content extraction failed.', [
                'document_id' => $documentId,
                'file_path' => $document->file_path,
                'error' => $exception->getMessage(),
            ]);

            return [
                'document_id' => $documentId,
                'title' => $document->title,
                'status' => 'failed',
                'source' => 'local_file',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function hasContent(Document $document): bool
    {
        return DocumentContent::where('document_id', (string) $document->getKey())->exists();
    }

    public function extractAndStore(Document $document): void
    {
        $this->processDocument($document);
    }

    // -------------------------------------------------------------------------
    // Local PDF/image extraction and OCR
    // -------------------------------------------------------------------------

    private function extractAndStoreLocalFile(Document $document): int
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            throw new \Exception("File not found in public storage: {$document->file_path}");
        }

        $filePath = Storage::disk('public')->path($document->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $pages = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) => $this->handleImage($filePath),
            $extension === 'pdf' => $this->handlePdf($filePath),
            default => throw new \Exception("Unsupported file type: $extension"),
        };

        return $this->persistPages($document, $pages);
    }

    private function handlePdf(string $filePath): array
    {
        $pdf = (new Parser())->parseFile($filePath);
        $pages = [];

        foreach ($pdf->getPages() as $index => $page) {
            $pageNum = $index + 1;
            $text = trim($page->getText());

            if ($this->hasRealText($text)) {
                Log::info("Page $pageNum text layer found.");
                $pages[] = ['page' => $pageNum, 'content' => $text, 'type' => 'text', 'source' => 'local_file'];
                continue;
            }

            Log::info("Page $pageNum has no text layer, running OCR.");
            $pages[] = [
                'page' => $pageNum,
                'content' => $this->ocrPdfPage($filePath, $pageNum),
                'type' => 'ocr',
                'source' => 'local_file',
            ];
        }

        return $pages;
    }

    private function ocrPdfPage(string $filePath, int $pageNum): string
    {
        $tempDir = sys_get_temp_dir() . '/ocr_' . Str::uuid();
        mkdir($tempDir, 0755, true);

        try {
            $imageBase = $tempDir . '/page';

            exec(sprintf(
                'pdftoppm -singlefile -f %d -l %d -r 300 -png %s %s 2>&1',
                $pageNum,
                $pageNum,
                escapeshellarg($filePath),
                escapeshellarg($imageBase)
            ), $output, $exitCode);

            if ($exitCode !== 0) {
                Log::warning("pdftoppm failed on page $pageNum", ['output' => $output]);
                return '';
            }

            $imagePath = $imageBase . '.png';

            if (!file_exists($imagePath)) {
                Log::warning("Image not found for page $pageNum", ['path' => $imagePath]);
                return '';
            }

            return $this->runOcr($imagePath);
        } finally {
            $this->cleanupDir($tempDir);
        }
    }

    private function handleImage(string $filePath): array
    {
        Log::info('Standalone image, running OCR directly.');

        return [
            ['page' => 1, 'content' => $this->runOcr($filePath), 'type' => 'ocr', 'source' => 'local_file'],
        ];
    }

    private function runOcr(string $imagePath): string
    {
        try {
            return trim((new TesseractOCR($imagePath))->run());
        } catch (\Throwable $exception) {
            Log::error('OCR failed', ['image' => $imagePath, 'error' => $exception->getMessage()]);
            return '';
        }
    }

    // -------------------------------------------------------------------------
    // Open Library extraction
    // -------------------------------------------------------------------------

    private function fetchOpenLibraryPages(string $key): array
    {
        $work = $this->fetchOpenLibraryJson($this->openLibraryWorkPath($key));
        $editions = $this->fetchOpenLibraryEditions($key);
        $fullText = $this->fetchInternetArchiveText($work, $editions);

        if ($this->hasRealText($fullText)) {
            return $this->chunkText($fullText, 'open_library_full_text');
        }

        $metadataText = $this->openLibraryMetadataText($work, $editions);

        return $this->hasRealText($metadataText)
            ? [['page' => 1, 'content' => $metadataText, 'type' => 'open_library_metadata', 'source' => 'open_library']]
            : [];
    }

    private function fetchOpenLibraryJson(string $path, array $query = []): array
    {
        $response = Http::baseUrl($this->openLibraryBaseUrl())
            ->acceptJson()
            ->withUserAgent($this->openLibraryUserAgent())
            ->retry(3, 1000)
            ->timeout(30)
            ->get($path, $query);

        $response->throw();

        return $response->json() ?? [];
    }

    private function fetchOpenLibraryEditions(string $key): array
    {
        try {
            $payload = $this->fetchOpenLibraryJson($this->openLibraryWorkPath($key) . '/editions.json', [
                'limit' => self::OPEN_LIBRARY_EDITIONS_LIMIT,
            ]);
        } catch (\Throwable $exception) {
            Log::info('Open Library editions unavailable; using work metadata only.', [
                'open_library_key' => $key,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        return $payload['entries'] ?? [];
    }

    private function fetchInternetArchiveText(array ...$payloads): string
    {
        foreach (array_slice($this->internetArchiveIds($payloads), 0, self::INTERNET_ARCHIVE_ATTEMPTS_LIMIT) as $archiveId) {
            $text = $this->downloadInternetArchiveText($archiveId);

            if ($this->hasRealText($text)) {
                return $text;
            }
        }

        return '';
    }

    private function downloadInternetArchiveText(string $archiveId): string
    {
        try {
            $metadata = Http::baseUrl($this->internetArchiveBaseUrl())
                ->acceptJson()
                ->withUserAgent($this->openLibraryUserAgent())
                ->timeout(10)
                ->get('/metadata/' . rawurlencode($archiveId));

            if (!$metadata->successful()) {
                return '';
            }

            $files = $metadata->json('files') ?? [];
            $textFile = collect($files)
                ->first(fn (array $file) => str_ends_with(strtolower($file['name'] ?? ''), '_djvu.txt'))
                ?? collect($files)->first(fn (array $file) => str_ends_with(strtolower($file['name'] ?? ''), '.txt'));

            if (!$textFile || empty($textFile['name'])) {
                return '';
            }

            $response = Http::withUserAgent($this->openLibraryUserAgent())
                ->timeout(20)
                ->get(sprintf(
                    '%s/download/%s/%s',
                    $this->internetArchiveBaseUrl(),
                    rawurlencode($archiveId),
                    str_replace('%2F', '/', rawurlencode($textFile['name']))
                ));

            return $response->successful() ? trim($response->body()) : '';
        } catch (\Throwable $exception) {
            Log::warning('Internet Archive text fetch failed.', [
                'archive_id' => $archiveId,
                'error' => $exception->getMessage(),
            ]);

            return '';
        }
    }

    private function openLibraryMetadataText(array $work, array $editions): string
    {
        $parts = [
            $work['title'] ?? null,
            $this->descriptionToText($work['description'] ?? null),
            $this->descriptionToText($work['first_sentence'] ?? null),
            $this->excerptsToText($work['excerpts'] ?? []),
            $this->tableOfContentsToText($work['table_of_contents'] ?? []),
            $this->subjectsToText($work),
        ];

        foreach ($editions as $edition) {
            $parts[] = $edition['title'] ?? null;
            $parts[] = $this->descriptionToText($edition['description'] ?? null);
            $parts[] = $this->descriptionToText($edition['notes'] ?? null);
            $parts[] = $this->tableOfContentsToText($edition['table_of_contents'] ?? []);
        }

        return collect($parts)
            ->flatten()
            ->filter(fn ($part) => is_string($part) && trim($part) !== '')
            ->unique()
            ->implode("\n\n");
    }

    // -------------------------------------------------------------------------
    // Persistence & helpers
    // -------------------------------------------------------------------------

    private function persistPages(Document $document, array $pages): int
    {
        $stored = 0;

        foreach ($pages as $page) {
            if (empty(trim($page['content'] ?? ''))) {
                Log::info("Page {$page['page']} is blank, skipping.");
                continue;
            }

            DocumentContent::create([
                'document_id' => (string) $document->getKey(),
                'page_number' => $page['page'],
                'content' => $page['content'],
                'type' => $page['type'],
                'source' => $page['source'] ?? $page['type'],
            ]);

            $stored++;
        }

        return $stored;
    }

    private function descriptionToText(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value['value'] ?? null;
        }

        return null;
    }

    private function excerptsToText(array $excerpts): string
    {
        return collect($excerpts)
            ->map(fn (array $excerpt) => $excerpt['excerpt'] ?? null)
            ->filter()
            ->implode("\n\n");
    }

    private function tableOfContentsToText(array $items): string
    {
        return collect($items)
            ->map(fn (array $item) => $item['title'] ?? $item['label'] ?? null)
            ->filter()
            ->implode("\n");
    }

    private function subjectsToText(array $work): string
    {
        $subjects = collect([
            $work['subjects'] ?? [],
            $work['subject_people'] ?? [],
            $work['subject_places'] ?? [],
            $work['subject_times'] ?? [],
        ])->flatten()->filter()->unique()->values();

        return $subjects->isEmpty() ? '' : 'Subjects: ' . $subjects->implode(', ');
    }

    private function internetArchiveIds(array $payloads): array
    {
        $items = [];

        foreach ($payloads as $payload) {
            if (array_is_list($payload)) {
                $items = array_merge($items, $payload);
            } else {
                $items[] = $payload;
            }
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->flatMap(fn (array $item) => collect([
                $item['ocaid'] ?? null,
                $item['ia'] ?? [],
                $item['internet_archive_id'] ?? null,
            ])->flatten()->all())
            ->filter(fn ($id) => is_string($id) && trim($id) !== '')
            ->map(fn (string $id) => trim($id))
            ->unique()
            ->values()
            ->all();
    }

    private function chunkText(string $text, string $type): array
    {
        return collect(str_split(trim($text), self::OPEN_LIBRARY_CHUNK_LENGTH))
            ->map(fn (string $chunk, int $index) => [
                'page' => $index + 1,
                'content' => trim($chunk),
                'type' => $type,
                'source' => 'open_library',
            ])
            ->filter(fn (array $page) => $page['content'] !== '')
            ->values()
            ->all();
    }

    private function openLibraryKey(Document $document): ?string
    {
        foreach (['open_library_key', 'openlibrary_key', 'open_library_id', 'openlibrary_id', 'ol_key'] as $field) {
            $value = $document->getAttribute($field);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function openLibraryWorkPath(string $key): string
    {
        $key = trim($key);
        $key = str_replace(['https://openlibrary.org', 'http://openlibrary.org'], '', $key);
        $key = '/' . ltrim($key, '/');

        return preg_replace('/\.json$/', '', $key);
    }

    private function openLibraryBaseUrl(): string
    {
        return rtrim((string) config('services.open_library.base_url', 'https://openlibrary.org'), '/');
    }

    private function internetArchiveBaseUrl(): string
    {
        return rtrim((string) config('services.internet_archive.base_url', 'https://archive.org'), '/');
    }

    private function openLibraryUserAgent(): string
    {
        return (string) config('services.open_library.user_agent', 'TurathBackend (configure OPEN_LIBRARY_USER_AGENT)');
    }

    private function hasRealText(string $text): bool
    {
        return strlen(trim($text)) >= self::MIN_TEXT_LENGTH;
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob("$dir/*") as $file) {
            is_dir($file) ? $this->cleanupDir($file) : unlink($file);
        }

        rmdir($dir);
    }
}

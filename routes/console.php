<?php

use App\Jobs\ImportOpenLibraryBooksJob;
use App\Jobs\StoreDocumentContentJob;
use App\Models\Document;
use App\Services\DocumentContentServices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('open-library:sync {--subject=* : Open Library subject names to import} {--limit= : Number of books per subject, max 100} {--ebooks-only : Import only works with ebooks}', function () {
    $subjects = $this->option('subject') ?: [];
    $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
    $ebooksOnly = (bool) $this->option('ebooks-only');

    ImportOpenLibraryBooksJob::dispatch($subjects, $limit, $ebooksOnly)
        ->onConnection(config('services.open_library.queue_connection', 'background'))
        ->onQueue(config('services.open_library.queue', 'open-library'));

    $this->info('Open Library sync queued in the background.');
})->purpose('Queue an Open Library book import.');

Artisan::command('documents:extract-content {--limit= : Maximum number of documents to check} {--queue : Queue jobs instead of processing now}', function () {
    $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
    $queue = (bool) $this->option('queue');
    $contentService = app(DocumentContentServices::class);

    if ($queue) {
        $query = Document::query()->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $queued = 0;
        $skipped = 0;

        foreach ($query->get() as $document) {
            if ($contentService->hasContent($document)) {
                $skipped++;
                continue;
            }

            StoreDocumentContentJob::dispatch($document);
            $queued++;
        }

        $this->info("Queued {$queued} document content extraction jobs.");
        $this->line("Skipped {$skipped} documents with existing content.");

        return;
    }

    $summary = $contentService->processAllMissing($limit);

    $this->info("Checked {$summary['checked']} documents.");
    $this->line("Processed: {$summary['processed']}");
    $this->line("Skipped existing content: {$summary['skipped']}");
    $this->line("No source: {$summary['no_source']}");
    $this->line("Failed: {$summary['failed']}");
})->purpose('Extract missing document content from Open Library, PDF text layers, or OCR.');

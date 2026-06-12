<?php

use App\Jobs\ImportOpenLibraryBooksJob;
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

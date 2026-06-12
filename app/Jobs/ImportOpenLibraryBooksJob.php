<?php

namespace App\Jobs;

use App\Services\OpenLibraryImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportOpenLibraryBooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $subjects = [],
        public ?int $limit = null,
        public bool $ebooksOnly = false
    ) {}

    public function backoff(): array
    {
        return [60, 180, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(OpenLibraryImportService $importService): void
    {
        Log::info('Starting Open Library import.', [
            'subjects' => $this->subjects,
            'limit' => $this->limit,
            'ebooks_only' => $this->ebooksOnly,
        ]);

        $summary = $importService->sync(
            $this->subjects,
            $this->limit,
            $this->ebooksOnly
        );

        Log::info('Open Library import finished.', $summary);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Open Library import failed.', [
            'error' => $exception->getMessage(),
            'subjects' => $this->subjects,
        ]);
    }
}

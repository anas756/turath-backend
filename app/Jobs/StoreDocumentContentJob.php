<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentContentServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreDocumentContentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Document $document
    ) {}

    /**
     * Get the unique ID for the job to prevent duplicate processing.
     */
    public function uniqueId(): string
    {
        return (string) $this->document->id;
    }

    /**
     * The backoff strategy for retries (in seconds).
     */
    public function backoff(): array
    {
        return [60, 120, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentContentServices $contentService): void
    {
        // Increase memory limit for heavy OCR processing
        ini_set('memory_limit', '512M');

        // Check if the document still exists in the database
        if (!$this->document->exists) {
            return;
        }

        Log::info("Processing document: {$this->document->id}", [
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $contentService->processDocument($this->document);

            Log::info("Done processing document: {$this->document->id}", $result);
        } catch (\Throwable $e) {
            // Re-throw to allow the queue to handle retries/failures
            Log::error("Error processing document {$this->document->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job permanently failed for document: {$this->document->id}", [
            'error'   => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }
}

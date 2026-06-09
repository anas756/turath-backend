<?php
// app/Services/DocumentContentServices.php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class DocumentContentServices
{
    private const MIN_TEXT_LENGTH = 20;

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
                    'matches'     => $pages->map(fn($page) => [
                        'page_number' => $page->page_number,
                        'snippet'     => mb_strimwidth($page->content, 0, 120, '...'),
                    ]),
                ];
            })
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Extraction & OCR
    // -------------------------------------------------------------------------

    public function extractAndStore(Document $document): void
    {
        $filePath  = Storage::disk('public')->path($document->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $pages = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) => $this->handleImage($filePath),
            $extension === 'pdf'                                  => $this->handlePdf($filePath),
            default => throw new \Exception("Unsupported file type: $extension"),
        };

        $this->persistPages($document, $pages);
    }

    private function handlePdf(string $filePath): array
    {
        $pdf   = (new Parser())->parseFile($filePath);
        $pages = [];

        foreach ($pdf->getPages() as $index => $page) {
            $pageNum = $index + 1;
            $text    = trim($page->getText());

            if ($this->hasRealText($text)) {
                Log::info("Page $pageNum — text layer found.");
                $pages[] = ['page' => $pageNum, 'content' => $text, 'type' => 'text'];
            } else {
                Log::info("Page $pageNum — no text layer, running OCR.");
                $pages[] = ['page' => $pageNum, 'content' => $this->ocrPdfPage($filePath, $pageNum), 'type' => 'ocr'];
            }
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
                'pdftoppm -f %d -l %d -r 300 -png %s %s 2>&1',
                $pageNum,
                $pageNum,
                escapeshellarg($filePath),
                escapeshellarg($imageBase)
            ), $output, $exitCode);

            if ($exitCode !== 0) {
                Log::warning("pdftoppm failed on page $pageNum", ['output' => $output]);
                return '';
            }

            $imagePath = sprintf('%s-%03d.png', $imageBase, $pageNum);

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
        Log::info("Standalone image — running OCR directly.");

        return [
            ['page' => 1, 'content' => $this->runOcr($filePath), 'type' => 'ocr']
        ];
    }

    private function runOcr(string $imagePath): string
    {
        try {
            return trim((new TesseractOCR($imagePath))->run());
        } catch (\Throwable $e) {
            Log::error("OCR failed", ['image' => $imagePath, 'error' => $e->getMessage()]);
            return '';
        }
    }

    private function persistPages(Document $document, array $pages): void
    {
        foreach ($pages as $page) {
            if (empty(trim($page['content']))) {
                Log::info("Page {$page['page']} is blank — skipping.");
                continue;
            }

            DocumentContent::create([
                'document_id' => $document->id,
                'page_number' => $page['page'],
                'content'     => $page['content'],
                'type'        => $page['type'],
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function hasRealText(string $text): bool
    {
        return strlen(trim($text)) >= self::MIN_TEXT_LENGTH;
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) return;

        foreach (glob("$dir/*") as $file) {
            is_dir($file) ? $this->cleanupDir($file) : unlink($file);
        }

        rmdir($dir);
    }
}

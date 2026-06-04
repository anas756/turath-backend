<?php

namespace App\Services;

use App\Models\DocumentContent;

class DocumentContentServices
{
    /**
     * Get content page-by-page.
     */
    public function getContentByDocument($document_id)
    {
        return DocumentContent::where('document_id', $document_id)
            ->orderBy('page_number', 'asc')
            ->paginate(1);
    }

    /**
     * Search inside a targeted document.
     */
    public function searchInsideDocument($keyWord, $document_id)
    {
        return DocumentContent::where('document_id', $document_id)
            ->where('content', 'like', '%' . $keyWord . '%')
            ->orderBy('page_number', 'asc')
            ->get(['page_number', 'content']);
    }

    /**
     * Search across all document contents and group matches neatly by document.
     */
    public function searchLibrary($keyWord)
    {
        // Fetch matching documents with parent metadata (assuming relationship 'document' exists in DocumentContent model)
        $rawResults = DocumentContent::where('content', 'like', '%' . $keyWord . '%')
            ->with('document')
            ->get();

        // Transform collection to group cleanly by document_id
        return $rawResults->groupBy('document_id')->map(function ($pages) {
            $document = $pages->first()->document;

            return [
                'document_id'   => $document?->id,
                'title'         => $document?->title,
                'cover_url'     => $document?->cover_url,
                'matches'       => $pages->map(function ($page) {
                    return [
                        'page_number' => $page->page_number,
                        'snippet'     => mb_strimwidth($page->content, 0, 120, '...')
                    ];
                })
            ];
        })->values()->all();
    }
}

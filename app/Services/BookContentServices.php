<?php

namespace App\Services;

use App\Models\BookContent;

class BookContentServices
{
    /**
     * Get content page-by-page.
     */
    public function getContentByBook($book_id)
    {
        // Fixed: changed 'page_number' argument inside paginate to 1 page per view
        return BookContent::where('book_id', $book_id)
            ->orderBy('page_number', 'asc')
            ->paginate(1);
    }

    /**
     * Search inside a targeted book.
     */
    public function searchInsideBook($keyWord, $book_id)
    {
        return BookContent::where('book_id', $book_id)
            ->where('content', 'like', '%' . $keyWord . '%')
            ->orderBy('page_number', 'asc')
            ->get(['page_number', 'content']);
    }

    /**
     * Search across all book contents and group matches neatly by book.
     */
    public function searchLibrer($keyWord)
    {
        // Fetch matching documents with parent metadata
        $rawResults = BookContent::where('content', 'like', '%' . $keyWord . '%')
            ->with('book')
            ->get();

        //  Transform collection to group cleanly by book_id for the user interface
        return $rawResults->groupBy('book_id')->map(function ($pages) {
            $book = $pages->first()->book;

            return [
                'book_id'     => $book?->id,
                'book_title'  => $book?->title,
                'cover'       => $book?->cover,
                'matches'     => $pages->map(function ($page) {
                    return [
                        'page_number' => $page->page_number,
                        'snippet'     => mb_strimwidth($page->content, 0, 120, '...') 
                    ];
                })
            ];
        })->values()->all();
    }
}

<?php

namespace App\Services;

use App\Models\Book;
use Exception;

class BookServices
{
    /**
     * Provide all books with their categories.
     */
    public function index()
    {
        // Added ->get() to execute the query and fetch the collection
        return Book::with('categories')->get();
    }

    /**
     * Store a new book.
     */
    public function store(array $data)
    {
        return Book::create($data);
    }

    /**
     * Update a book unless it belongs to the Open Library API.
     */
    public function update(Book $book, array $data)
    {
        // Check if book has open library id 
        if ($this->hasOpenLibraryId($book)) {
            throw new Exception("Cannot update books synced from the Open Library API.");
        }

        // Perform update
        $book->update($data);

        // Return the updated book instance
        return $book->fresh();
    }

    /**
     * Delete a book unless it belongs to the Open Library API.
     */
    public function delete(Book $book)
    {
        // Check if book has open library id 
        if ($this->hasOpenLibraryId($book)) {
            throw new Exception("Cannot delete books synced from the Open Library API.");
        }

        // Perform delete
        return $book->delete();
    }

    /**
     * Check if the book has an open_library_id.
     * Returns true if it exists and is not null/empty.
     */
    public function hasOpenLibraryId(Book $book): bool
    {
        return !empty($book->open_library_id);
    }
}

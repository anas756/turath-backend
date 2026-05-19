<?php

namespace App\Services;

use App\Models\Book;
use Exception;
use Illuminate\Http\UploadedFile;

class BookServices
{
    /**
     * Provide all books with their categories.
     */
    public function index()
    {
        return Book::with('categories')->get();
    }

    /**
     * Store a new book.
     */
    public function store(array $data)
    {
        // Check if the data array contains a valid uploaded file instance
        if (isset($data['file_path']) && $data['file_path'] instanceof UploadedFile) {
            // Save file to storage and replace the file object with the path string
            $data['file_path'] = $this->storeFile($data['file_path']);
        }
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

    // store file in storage 
    public function storeFile($file)  {
        return $file->store('books', 'public');
    }

}

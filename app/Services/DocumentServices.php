<?php

namespace App\Services;

use App\Models\Document;
use Exception;
use Illuminate\Http\UploadedFile;

class DocumentServices
{
    /**
     * Provide all documents with their categories.
     */
    public function index()
    {
        // T-a9ed blli smit l-method f Document model hia 'categorie' (singular)
        return Document::with('categorie')->get();
    }

    /**
     * Store a new document.
     */
    public function store(array $data)
    {
        // Check if the data array contains a valid uploaded file instance
        if (isset($data['file_path']) && $data['file_path'] instanceof UploadedFile) {
            $data['file_path'] = $this->storeFile($data['file_path']);
        }

        return Document::create($data);
    }

    /**
     * Update a document unless it belongs to the Open Library API.
     */
    public function update(Document $document, array $data)
    {
        // Check if document has open library key 
        if ($this->hasOpenLibraryId($document)) {
            throw new Exception("Cannot update documents synced from the Open Library API.");
        }

        // Perform update
        $document->update($data);

        return $document->fresh();
    }

    /**
     * Delete a document unless it belongs to the Open Library API.
     */
    public function delete(Document $document)
    {
        // Check if document has open library key 
        if ($this->hasOpenLibraryId($document)) {
            throw new Exception("Cannot delete documents synced from the Open Library API.");
        }

        // Perform delete
        return $document->delete();
    }

    /**
     * Check if the document has an open_library_key.
     */
    public function hasOpenLibraryId(Document $document): bool
    {
        return !empty($document->open_library_key);
    }

    /**
     * Store file in storage.
     */
    public function storeFile(UploadedFile $file)
    {
        return $file->store('documents', 'public');
    }
}

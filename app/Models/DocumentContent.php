<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DocumentContent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'document_contents';

    protected $fillable = [
        'document_id',
        'page_number',
        'content',
        'type',
        'source',
    ];

    /**
     * Get the book that owns this page content.
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}

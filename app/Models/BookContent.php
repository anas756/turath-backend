<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BookContent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'book_contents';

    protected $fillable = [
        'book_id',
        'page_number',
        'content',
    ];

    /**
     * Get the book that owns this page content.
     */
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}

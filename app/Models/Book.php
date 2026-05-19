<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Book extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'books';

    protected $fillable = [
        'title',
        'open_library_id',
        'description',
        'authors',
        'cover',
        'file_path',
        'categorie_id',
        'tags',
    ];

    // relations 
    // categories
    public function categories()  {
        return $this->belongsTo(Categorie::class , 'categorie_id');
    }
    // book_contents
    public function contents()  {
        return  $this->hasMany(BookContent::class , 'book_id');
    }
}

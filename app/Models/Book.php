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
        'categorie_id',
        'tags',
    ];

    // relations 
    public function categories()  {
        return $this->belongsTo(Categorie::class , 'categorie_id');
    }
}

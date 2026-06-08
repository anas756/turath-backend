<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Document extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'documents';

  
    protected $fillable = [
        'title',
        'description',
        'authors',
        'cover',         
        'file_path',           
        'source',              
        'open_library_key',    
        'categorie_id',       
        'user_id',             
        'tags',                
    ];

    // relations 
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->contents()->delete();
        });
    }
    // categories
    public function categorie()  {
        return $this->belongsTo(Categorie::class , 'categorie_id');
    }
    // book_contents
    public function contents()  {
        return  $this->hasMany(DocumentContent::class , 'book_id');
    }
}

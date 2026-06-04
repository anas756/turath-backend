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
        'cover_url',         
        'file_path',           
        'source',              
        'open_library_key',    
        'categorie_id',       
        'user_id',             
        'tags',                
    ];

    // relations 
    // categories
    public function categories()  {
        return $this->belongsTo(Categorie::class , 'categorie_id');
    }
    // book_contents
    public function contents()  {
        return  $this->hasMany(DocumentContent::class , 'book_id');
    }
}

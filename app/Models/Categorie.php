<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Categorie extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'categories';

    protected $fillable = [
        'name' , 
        'description',
        'slug' , 
        'icon',
        'banner',
    ];
    // relations 
    public function books()  {
        return $this->hasMany(Book::class , 'categorie_id');
    }
}

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
    // DELETING SHILED WHEN THE PARENT DELETED
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($categorie) {
            $categorie->documents()->each(function ($document) {
                $document->delete();
            });
        });
    }
    // relations 
    public function documents()  {
        return $this->hasMany(Document::class , 'categorie_id');
    }
}

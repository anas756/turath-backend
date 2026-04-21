<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model; // Critical: Import MongoDB Model
use MongoDB\Laravel\Eloquent\SoftDeletes; // Add this for deleted_at handling
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    /** * MongoDB Connection 
     * Only necessary if your default connection is not mongodb 
     */
    protected $connection = 'mongodb';
    protected $collection = 'users'; // In MongoDB, tables are called "collections"

    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Removed timestamps from fillable; Laravel handles those automatically.
     */
    protected $fillable = [
        'name',
        'userName',
        'email',
        'password',
        'role',
        'confirmed',
        'is_login',
        'user_token',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'user_token', 
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'confirmed' => 'boolean',
            'is_login' => 'boolean',
            'last_login' => 'datetime',
        ];
    }

  
}

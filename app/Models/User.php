<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'userName',
        'email',
        'password',
        'role',
        'confirmed',
        'is_login',
        'auth_tokens',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'auth_tokens',
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

    // get JWT key
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // check auth id with same user id or role admin
    public function CheckUserAuthOrAdminRole($userAuth)
    {
        return $this->id == $userAuth->id || $userAuth->role == "admin";
    }

    /**
     * ✅ ADD THIS METHOD - Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * ✅ ADD THIS METHOD - Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}

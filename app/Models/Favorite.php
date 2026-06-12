<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'favorites';

    protected $fillable = [
        'user_id',
        'favorable_id',
        'favorable_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Polymorphic relation — supports Document and Media (and any future type)
    |
    | Usage:
    |   $favorite->favorable   → the Document or Media instance
    |--------------------------------------------------------------------------
    */
    public function favorable()
    {
        return $this->morphTo(
            __FUNCTION__,
            'favorable_type',
            'favorable_id'
        );
    }

    // -------------------------------------------------------------------------
    // Convenience morphTo aliases
    // -------------------------------------------------------------------------

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'favorable_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'favorable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('favorable_type', $type);
    }

    public function scopeDocuments($query)
    {
        return $query->where('favorable_type', Document::class);
    }

    public function scopeMedia($query)
    {
        return $query->where('favorable_type', Media::class);
    }
}

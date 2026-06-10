<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    // Specify the collection name
    protected $collection = 'media';

    // Define connection
    protected $connection = 'mongodb';

    // Define fillable fields
    protected $fillable = [
        'title',
        'type',
        'format',
        'resolution',
        'size',
        'curator',
        'status',
        'user_id',      // Add user_id for relationship
        'date_added',
    ];

    // Casts for proper data types
    protected $casts = [
        'size' => 'integer',
        'date_added' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Default values
    protected $attributes = [
        'status' => 'active',
    ];

    // Boot method to set default date_added
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->date_added) {
                $model->date_added = now();
            }

            // Auto-set curator from user if not provided
            if (!$model->curator && $model->user_id) {
                $user = \App\Models\User::find($model->user_id);
                if ($user) {
                    $model->curator = $user->name;
                }
            }
        });
    }

    // Relationship with User (since MongoDB doesn't support foreign keys traditionally)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes for common queries
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessor for formatted size (KB, MB, etc.)
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

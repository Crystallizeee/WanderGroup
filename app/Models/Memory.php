<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memory extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'file_path',
        'location',
        'caption',
        'ai_tags',
        'taken_at',
        'is_highlighted',
        'likes_count',
    ];

    protected $casts = [
        'ai_tags' => 'array',
        'taken_at' => 'datetime',
        'likes_count' => 'integer',
    ];

    protected function isHighlighted(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => in_array($value, [1, '1', true, 'true', 't', 'y', 'yes'], true),
            set: fn ($value) => (bool) $value,
        );
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a directly renderable image URL.
     * Uses lh3.googleusercontent.com/d/{id} which serves image/jpeg with HTTP 200 directly.
     */
    public function getImageUrlAttribute()
    {
        $path = $this->file_path;

        // Extract Google Drive file ID from any known URL format
        if (str_contains($path, 'drive.google.com') || str_contains($path, 'googleapis.com') || str_contains($path, 'googleusercontent.com')) {
            $fileId = null;

            // Format: /file/d/{ID}
            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $path, $m)) {
                $fileId = $m[1];
            }
            // Format: ?id={ID} or &id={ID}  (uc?id=... or thumbnail?id=...)
            elseif (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $path, $m)) {
                $fileId = $m[1];
            }
            // Format: lh3.googleusercontent.com/d/{ID}
            elseif (preg_match('/googleusercontent\.com\/d\/([a-zA-Z0-9_-]+)/', $path, $m)) {
                $fileId = $m[1];
            }

            if ($fileId) {
                // lh3.googleusercontent.com/d/{id} returns image/jpeg directly (HTTP 200, no redirect)
                return "https://lh3.googleusercontent.com/d/{$fileId}";
            }
        }

        // Local file: serve via proxy route
        return route('trips.memories.image', [$this->trip_id, $this->id]);
    }
}

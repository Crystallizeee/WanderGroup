<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'title',
        'file_path',
        'file_type',
        'file_size',
        'category',
        'is_available_offline',
    ];

    protected $casts = [
        'is_available_offline' => 'boolean',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

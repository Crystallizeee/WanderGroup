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

    protected $casts = [];

    protected function isAvailableOffline(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
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
}

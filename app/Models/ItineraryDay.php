<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryDay extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'date', 'day_number', 'notes'];

    protected $casts = ['date' => 'date'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function items()
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('sort_order')->orderBy('start_time');
    }
}

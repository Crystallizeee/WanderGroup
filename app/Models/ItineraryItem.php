<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_day_id', 'title', 'description', 'location', 'image_url',
        'location_lat', 'location_lng', 'start_time', 'end_time',
        'type', 'status', 'icon', 'sort_order', 'estimated_cost',
        'created_by',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
    ];

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'flight' => 'flight_takeoff',
            'hotel' => 'hotel',
            'restaurant' => 'restaurant',
            'transport' => 'directions_car',
            default => 'local_activity',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'primary',
            'tentative' => 'outline',
            'voting' => 'tertiary-container',
            'cancelled' => 'error',
            default => 'outline',
        };
    }
}

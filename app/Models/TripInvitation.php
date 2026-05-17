<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripInvitation extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'email', 'token', 'status', 'invited_by', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }

    public function isExpired(): bool { return $this->expires_at->isPast(); }
}

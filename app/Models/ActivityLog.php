<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'user_id', 'action', 'subject_type', 'subject_id', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function subject()
    {
        return $this->morphTo();
    }

    public static function log(int $tripId, int $userId, string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $metadata = null): self
    {
        return static::create([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
        ]);
    }
}

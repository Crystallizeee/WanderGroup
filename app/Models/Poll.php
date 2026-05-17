<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'question', 'description', 'type', 'status', 'deadline', 'created_by'];

    protected $casts = ['deadline' => 'datetime'];

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function options(): HasMany { return $this->hasMany(PollOption::class); }
    public function totalVotes(): int { return $this->options->sum(fn($o) => $o->votes()->count()); }
}

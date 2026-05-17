<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'destination', 'description', 'cover_image',
        'start_date', 'end_date', 'budget', 'currency',
        'status', 'created_by', 'invite_code',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    /**
     * Get or generate a unique invite code for the trip.
     */
    public function getInviteCode(): string
    {
        if ($this->invite_code) {
            return $this->invite_code;
        }

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->destination), 0, 4)) . '-' . \Illuminate\Support\Str::random(4);
        
        $this->update(['invite_code' => $code]);

        return $code;
    }

    // ── Relationships ──
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function organizers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'organizer');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TripInvitation::class);
    }

    public function itineraryDays(): HasMany
    {
        return $this->hasMany(ItineraryDay::class)->orderBy('date');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->orderByDesc('expense_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->orderByDesc('created_at');
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class)->orderByDesc('created_at');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderByDesc('created_at');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    // ── Scopes ──
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planning', 'active']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ── Helpers ──
    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOrganizer(User $user): bool
    {
        return $this->organizers()->where('user_id', $user->id)->exists();
    }

    public function totalSpent(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    public function daysUntilTrip(): int
    {
        return (int) now()->diffInDays($this->start_date, false);
    }

    public function duration(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }
}

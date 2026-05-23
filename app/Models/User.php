<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar', 'timezone', 'bio', 'travel_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'travel_preferences' => 'array',
        ];
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function createdTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'created_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'paid_by');
    }

    public function getInitialsAttribute(): string
    {
        $names = explode(' ', trim($this->name));
        if (count($names) >= 2) {
            return strtoupper(substr($names[0], 0, 1) . substr(end($names), 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar && (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://'))) {
            return $this->avatar;
        }
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }
}

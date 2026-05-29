<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'title', 'category', 'assigned_to', 'is_checked', 'is_shared', 'quantity', 'created_by'];

    protected $casts = [];

    protected function isChecked(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
        );
    }

    protected function isShared(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
        );
    }
    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

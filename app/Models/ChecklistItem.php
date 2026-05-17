<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'title', 'category', 'assigned_to', 'is_checked', 'is_shared', 'quantity', 'created_by'];

    protected $casts = ['is_checked' => 'boolean', 'is_shared' => 'boolean'];

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

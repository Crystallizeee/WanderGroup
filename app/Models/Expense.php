<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_id', 'title', 'amount', 'currency', 'category',
        'icon', 'receipt_image', 'paid_by', 'split_method',
        'notes', 'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'accommodation' => 'hotel',
            'food' => 'restaurant',
            'transport' => 'directions_car',
            'activity' => 'local_activity',
            'shopping' => 'shopping_bag',
            default => 'receipt_long',
        };
    }
}

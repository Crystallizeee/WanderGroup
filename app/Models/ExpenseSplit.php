<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSplit extends Model
{
    use HasFactory;

    protected $fillable = ['expense_id', 'user_id', 'amount', 'is_settled'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected function isSettled(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => in_array($value, [1, '1', true, 'true', 't', 'y', 'yes'], true),
            set: fn ($value) => (bool) $value,
        );
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

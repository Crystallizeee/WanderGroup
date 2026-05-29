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
            get: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
            set: fn ($value) => $value === 't' ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN),
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

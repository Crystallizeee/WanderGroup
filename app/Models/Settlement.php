<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'from_user', 'to_user', 'amount', 'status', 'payment_method'];

    protected $casts = ['amount' => 'decimal:2'];

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'from_user'); }
    public function payee(): BelongsTo { return $this->belongsTo(User::class, 'to_user'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $fillable = [
        'expense_id',
        'name',
        'price',
        'assigned_to',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

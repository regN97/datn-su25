<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegisterSession extends Model
{
    use SoftDeletes;

    protected $table = 'cash_register_sessions';

    protected $fillable = [
        'user_id',
        'user_shift_id',
        'opening_amount',
        'closing_amount',
        'actual_amount',
        'difference',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userShift()
    {
        return $this->belongsTo(UserShift::class, 'user_shift_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'session_id');
    }
}
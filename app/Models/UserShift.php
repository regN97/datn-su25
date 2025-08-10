<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserShift extends Model
{
    use SoftDeletes;

    protected $table = 'user_shifts';

    protected $fillable = [
        'user_id',
        'shift_id',
        'date',
        'status',
        'check_in',
        'total_hours',
        'check_out',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'status' => 'string',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
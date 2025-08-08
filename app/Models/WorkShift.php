<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkShift extends Model
{
    use SoftDeletes;

    protected $table = 'work_shifts';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'deleted_at' => 'datetime',
    ];

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'shift_id');
    }
}
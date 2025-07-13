<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class POStatus extends Model
{
    protected $table = 'po_statuses';
    protected $fillable = [
        'id',
        'name',
        'code',
    ];
}

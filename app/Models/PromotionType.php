<?php

namespace App\Models;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Model;

class PromotionType extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'type_id');
    }
}
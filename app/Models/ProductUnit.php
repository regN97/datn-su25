<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    // Những trường có thể gán hàng loạt (mass assignable)
    protected $fillable = [
        'name',
        'description',
    ];

}

<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductUnit extends Model
{
    use HasFactory;

    // Những trường có thể gán hàng loạt (mass assignable)
    protected $fillable = [
        'name',
        'description',
    ];
 public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}

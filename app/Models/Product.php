<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'description',
        'category_id',
        'unit_id',
        'purchase_price',
        'selling_price',
        'image_url',
        'min_stock_level',
        'max_stock_level',
        'is_active',
    ];
}

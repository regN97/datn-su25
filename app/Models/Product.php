<?php

namespace App\Models;

use App\Models\Category;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}

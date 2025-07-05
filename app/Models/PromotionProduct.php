<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProduct extends Pivot
{
    protected $table = 'promotion_products';

    protected $fillable = [
        'promotion_id',
        'product_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
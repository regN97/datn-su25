<?php

namespace App\Models;

use App\Models\Product;
use App\Models\PromotionType;
use App\Models\PromotionProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type_id',
        'discount_value',
        'min_order_value',
        'buy_quantity',
        'get_quantity',
        'coupon_code',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_limit_per_customer',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'discount_value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function promotionType()
    {
        return $this->belongsTo(PromotionType::class, 'type_id');
    }

    public function promotionProducts()
    {
        return $this->hasMany(PromotionProduct::class, 'promotion_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_products', 'promotion_id', 'product_id');
    }
}
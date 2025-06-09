<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'purchase_price',
        'initial_quantity',
        'current_quantity'
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}

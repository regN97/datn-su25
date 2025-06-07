<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'purchase_price',
        'initial_quantity',
        'current_quantity',
        'status', // Enum: 'active', 'low_stock', 'out_of_stock', 'expired'
        'supplier_id',
        'received_date',
        'invoice_number',
        'notes',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

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
}

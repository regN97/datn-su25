<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
     protected $table = 'inventory';
    protected $fillable = [
        'product_id',
        'batch_id',
        'quantity',
        'stock_status', // Enum: 'in_stock', 'low_stock', 'out_of_stock'
    ];
}

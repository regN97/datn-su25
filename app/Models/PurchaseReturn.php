<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'return_number',
        'purchase_order_id',
        'supplier_id',
        'status', // Enum: 'pending', 'approved', 'completed', 'rejected'
        'return_date',
        'reason',
        'total_items_returned',
        'total_value_returned',
        'created_by',
    ];
}

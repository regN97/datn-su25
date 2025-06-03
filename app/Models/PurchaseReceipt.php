<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'total_items_received',
        'total_value_received',
        'is_partial',
        'receipt_date',
        'received_by',
        'notes',
    ];
}

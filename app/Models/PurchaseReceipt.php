<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceipt extends Model
{
    use SoftDeletes;
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

     public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}

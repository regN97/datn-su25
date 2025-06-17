<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceiptItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'product_name',
        'product_sku',
        'quantity_received',
        'unit_cost',
        'subtotal',
    ];
}

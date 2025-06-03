<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity_ordered',
        'quantity_received',
        'quantity_returned',
        'unit_cost',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'notes',
    ];

}

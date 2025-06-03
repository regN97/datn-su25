<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{

    protected $fillable = [
        'po_number',
        'supplier_id',
        'status_id',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal_amount',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'payment_status',
        'payment_terms',
        'payment_method',
        'payment_due_date',
        'amount_paid',
        'received_status',
        'user_id',
        'approved_by',
        'approved_at',
        'notes',
    ];


}
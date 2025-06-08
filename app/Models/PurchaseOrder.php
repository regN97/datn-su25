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
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    public function status(){
        return $this->belongsTo(POStatus::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    
    public function purchaseReceipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
}
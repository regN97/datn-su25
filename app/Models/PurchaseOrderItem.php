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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Thêm relationship với BatchItem
    public function batchItems()
    {
        return $this->hasMany(BatchItem::class);
    }

    // Thêm method tính số lượng đã nhập
    public function getReceivedQuantity()
    {
        return $this->batchItems()->sum('received_quantity');
    }

    // Thêm method tính số lượng còn phải nhập
    public function getRemainingQuantity()
    {
        return $this->quantity - $this->getReceivedQuantity();
    }
}

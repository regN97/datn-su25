<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name',
        'product_sku',
        'ordered_quantity',
        'received_quantity',
        'quantity_returned',
        'unit_cost',
        'subtotal',
        'discount_amount',
        'discount_type',
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

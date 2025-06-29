<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'status_id',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'discount_amount',
        'discount_type',
        'total_amount',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function status()
    {
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

    // Thêm relationship với Batch
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    // Thêm method kiểm tra trạng thái nhập hàng
    public function getReceiptStatus()
    {
        $totalReceived = $this->batches()
            ->whereIn('receipt_status', ['completed', 'partially_received'])
            ->count();

        if ($totalReceived === 0) return 'pending';
        if ($this->isFullyReceived()) return 'completed';
        return 'partially_received';
    }

    // Thêm method kiểm tra đã nhập đủ hàng chưa
    public function isFullyReceived()
    {
        foreach ($this->items as $item) {
            $receivedQty = BatchItem::where('purchase_order_item_id', $item->id)
                ->sum('received_quantity');

            if ($receivedQty < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    public function updateStatusBasedOnItems()
    {
        $items = $this->items; // Quan hệ hasMany purchase_order_items
        $isFullyReceived = true;
        $hasAnyReceived = false;

        foreach ($items as $item) {
            $orderedQty = $item->ordered_quantity;

            $receivedQty = BatchItem::where('purchase_order_item_id', $item->id)
                ->sum('current_quantity');

            if ($receivedQty > 0) {
                $hasAnyReceived = true;
            }

            if ($receivedQty < $orderedQty) {
                $isFullyReceived = false;
            }
        }

        if ($isFullyReceived) {
            $this->status_id = 4; // completed
            $this->actual_delivery_date = Batch::where('purchase_order_id', $this->id)->max('received_date');
        } elseif ($hasAnyReceived) {
            $this->status_id = 3; // partially_received
            $this->actual_delivery_date = Batch::where('purchase_order_id', $this->id)->max('received_date');
        } else {
            $this->status_id = 2; // approved, chưa nhận gì
            $this->actual_delivery_date = null;
        }

        $this->save();
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'batch_number',
        'purchase_order_id',
        'supplier_id',
        'received_date',
        'invoice_number',
        'total_amount',
        'payment_status',
        'paid_amount',
        'receipt_status',
        'is_partial_receipt',
        'notes',
        'created_by',
        'updated_by'
    ];

    // Type casting cho các trường dữ liệu
    protected $casts = [
        'is_partial_receipt' => 'boolean',
        'received_date' => 'date',
        'total_amount' => 'integer',
        'paid_amount' => 'integer'
    ];

    public function batchItems()
    {
        return $this->hasMany(BatchItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper methods
    public function updateReceiptStatus()
    {
        $poFullyReceived = $this->purchaseOrder->isFullyReceived();
        $this->receipt_status = $poFullyReceived ? 'completed' : 'partially_received';
        $this->save();
    }
}

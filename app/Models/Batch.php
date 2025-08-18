<?php

namespace App\Models;

use App\Models\User;
use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'batches';
    protected $fillable = [
        'batch_number',
        'purchase_order_id',
        'supplier_id',
        'received_date',
        'invoice_number',
        'total_amount',
        'discount_type',
        'discount_amount',
        'payment_status',
        'payment_method',
        'payment_date',
        'paid_amount',
        'remaining_amount',
        'receipt_status',
        'notes',
        'status',
        'created_by',
        'updated_by'
    ];

    // Type casting cho các trường dữ liệu
    protected $casts = [
        'received_date' => 'date',
        'payment_date' => 'date',
        'payment_status' => 'string',
        'payment_method' => 'string',
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

    // Nếu muốn truy cập đến các Product thông qua BatchItem
    public function products()
    {
        return $this->hasManyThrough(Product::class, BatchItem::class, 'batch_id', 'id', 'id', 'product_id');
    }
    // Mối quan hệ với người dùng tạo
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Mối quan hệ với người dùng cập nhật
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

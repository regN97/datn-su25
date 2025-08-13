<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'return_number',
        'purchase_order_id',
        'supplier_id',
        'status', // Enum: 'pending', 'approved', 'completed', 'rejected'
        'payment_status', // ✅ Thêm dòng này - Enum: 'paid', 'unpaid'
        'return_date',
        'reason',
        'total_items_returned',
        'total_value_returned',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_value_returned' => 'decimal:2', // ✅ Thêm cast cho decimal
    ];

    protected $dates = ['return_date'];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
        // Hoặc có thể là tên class khác nếu bạn dùng tên khác cho chi tiết phiếu trả
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}

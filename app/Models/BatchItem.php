<?php

namespace App\Models;

use App\Models\User;
use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BatchItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'batch_items';
    protected $fillable = [
        'batch_id',
        'product_id',
        'purchase_order_item_id',
        'ordered_quantity',
        'received_quantity',
        'rejected_quantity',
        'remaining_quantity',
        'current_quantity',
        'purchase_price',
        'total_amount',
        'manufacturing_date',
        'expiry_date',
        'inventory_status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'purchase_price' => 'integer',
        'total_amount' => 'integer'
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updateInventoryStatus()
    {
        if ($this->current_quantity <= 0) {
            $this->inventory_status = 'out_of_stock';
        } elseif ($this->current_quantity <= $this->product->min_stock_level) {
            $this->inventory_status = 'low_stock';
        } else {
            $this->inventory_status = 'active';
        }
        $this->save();
    }
    // Mối quan hệ với người dùng tạo batch item
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Mối quan hệ với người dùng cập nhật batch item
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

<?php

namespace App\Models;

use App\Models\Bill;
use App\Models\User;
use App\Models\BatchItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryTransactionType;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_type_id',
        'product_id',
        'quantity_change',
        'stock_after',
        'unit_price',
        'total_value',
        'transaction_date',
        'related_bill_id',
        'related_purchase_return_id',
        'related_batch_id',
        'user_id',
        'note',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function transactionType()
    {
        return $this->belongsTo(InventoryTransactionType::class, 'transaction_type_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'related_bill_id');
    }

    public function batch()
    {
        return $this->belongsTo(BatchItem::class, 'related_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
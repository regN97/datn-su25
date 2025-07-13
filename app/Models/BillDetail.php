<?php

namespace App\Models;

use App\Models\Bill;
use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
    protected $fillable = [
        'bill_id', 'product_id', 'batch_id', 'p_name', 'p_sku', 'p_barcode',
        'quantity', 'unit_cost', 'unit_price', 'discount_per_item', 'subtotal',
        'created_at', 'updated_at',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(BatchItem::class, 'batch_id');
    }
}
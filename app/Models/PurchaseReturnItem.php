<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_return_id',
        'purchase_order_item_id',
        'product_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'product_name',
        'product_sku',
        'quantity_returned',
        'unit_cost',
        'subtotal',
        'reason',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

}

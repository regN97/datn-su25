<?php

namespace App\Models;

use App\Models\Customer;
use App\Models\BillDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_number', 'customer_id', 'sub_total', 'discount_amount', 'total_amount',
        'received_money', 'change_money', 'payment_method', 'payment_status_id',
        'notes', 'cashier_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(BillDetail::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bill_details', 'bill_id', 'product_id')
            ->withPivot(['quantity', 'unit_cost', 'unit_price', 'discount_per_item', 'subtotal']);
    }
}
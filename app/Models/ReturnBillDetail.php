<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnBillDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_bill_id',
        'product_id',
        'p_name',
        'returned_quantity',
        'unit_price',
        'subtotal',
    ];

    public function returnBill()
    {
        return $this->belongsTo(ReturnBill::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}   

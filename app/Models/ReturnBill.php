<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_bill_number',
        'bill_id',
        'customer_id',
        'cashier_id',
        'total_amount_returned',
        'reason',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function details()
    {
        return $this->hasMany(ReturnBillDetail::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
    
}

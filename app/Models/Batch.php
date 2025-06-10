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
        'manufacturing_date',
        'expiry_date',
        'status',
        'supplier_id',
        'received_date',
        'invoice_number',
        'notes'
    ];

    // Type casting cho các trường dữ liệu
    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'status' => 'string', // enum field
    ];

    // Định nghĩa các mối quan hệ
    public function supplier()
    {
        return $this->belongsTo(Supplier::class); // Assuming you have a Supplier model
    }

    public function products()
    {
        // This defines a many-to-many relationship with the 'product_batches' pivot table
        // withPivot specifies the extra columns from the pivot table you want to access
        return $this->belongsToMany(Product::class, 'product_batches', 'batch_id', 'product_id')
                    ->withPivot('purchase_price', 'initial_quantity', 'current_quantity')
                    ->withTimestamps(); // If you have created_at and updated_at on your pivot table
    }
}

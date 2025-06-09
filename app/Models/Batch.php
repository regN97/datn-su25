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
        return $this->belongsTo(Supplier::class); // One to Many (inverse)
    }

    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class); // One to Many
    }
}

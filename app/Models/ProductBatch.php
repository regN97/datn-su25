<?php

namespace App\Models;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Pivot
{
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

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

     public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}

<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductUnit;
use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'description',
        'category_id',
        'unit_id',
        'purchase_price',
        'selling_price',
        'image_url',
        'min_stock_level',
        'max_stock_level',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'selling_price' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'is_active' => 'boolean'
    ];

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'product_batches', 'product_id', 'batch_id')
            ->withPivot('purchase_price', 'initial_quantity', 'current_quantity')
            ->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_suppliers', 'product_id', 'supplier_id')->withTimestamps();
    }
    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}

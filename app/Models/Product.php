<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductUnit;
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
        'selling_price',
        'image_url',
        'min_stock_level',
        'max_stock_level',
        'is_active',
    ];

    protected $casts = [
        'selling_price' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'is_active' => 'boolean'
    ];

    public function batchItems()
    {
        return $this->hasMany(BatchItem::class);
    }

    /**
     * Get all batches containing this product
     */
    public function batches()
    {
        return $this->hasManyThrough(
            Batch::class,
            BatchItem::class,
            'product_id', // Foreign key on batch_items table
            'id', // Local key on batches table
            'id', // Local key on products table
            'batch_id' // Foreign key on batch_items table
        );
    }

    /**
     * Get current stock quantity of the product
     */
    public function getCurrentStock()
    {
        return $this->batchItems()
            ->where('inventory_status', 'active')
            ->sum('current_quantity');
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
        return $this->belongsToMany(Supplier::class, 'product_suppliers', 'product_id', 'supplier_id')->withTimestamps()->withPivot('purchase_price');
    }
        public function bills()
    {
        return $this->belongsToMany(Bill::class, 'bill_details', 'product_id', 'bill_id')
                    ->withPivot(['quantity', 'unit_cost', 'unit_price', 'discount_per_item', 'subtotal']);
    }
        public function billDetails()
    {
        return $this->hasMany(BillDetail::class);
    }
}

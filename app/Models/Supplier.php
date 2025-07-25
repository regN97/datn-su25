<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address'
    ];
     public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_suppliers')
                    ->withPivot('purchase_price');
    }
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}

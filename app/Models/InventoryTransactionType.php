<?php

namespace App\Models;

use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'transaction_type_id');
    }
}
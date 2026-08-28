<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDamageItem extends Model
{
    protected $fillable = [
        'inventory_damage_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'base_quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'base_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function inventoryDamage()
    {
        return $this->belongsTo(InventoryDamage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
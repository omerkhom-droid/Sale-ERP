<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningStockBalance extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'product_unit_id',
        'quantity',
        'base_quantity',
        'unit_cost',
        'total_cost',
        'notes',
        'created_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
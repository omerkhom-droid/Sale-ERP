<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBarcode extends Model
{
    protected $fillable = [
        'product_unit_id',
        'barcode',
        'is_default',
    ];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
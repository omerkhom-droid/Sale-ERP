<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'factor',
        'purchase_price',
        'sale_price',
        'minimum_sale_price',
        'is_default',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function barcode()
    {
        return $this->hasOne(ProductBarcode::class)
            ->where('is_default', true);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }
}
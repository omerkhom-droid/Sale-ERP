<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopOrderItem extends Model
{
    protected $fillable = ['product_id', 'product_unit_id', 'description', 'quantity', 'unit_price', 'vat_rate', 'fulfillment_status'];
    public function product() { return $this->belongsTo(Product::class); }
    public function productUnit() { return $this->belongsTo(ProductUnit::class); }
}

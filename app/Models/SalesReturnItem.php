<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'sales_invoice_item_id',
        'product_id',
        'product_unit_id',

        'product_name',
        'product_sku',
        'unit_name',

        'quantity',
        'base_quantity',

        'unit_price',
        'unit_cost',
        'total_cost',

        'discount_amount',
        'net_amount',

        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesInvoiceItem()
    {
        return $this->belongsTo(SalesInvoiceItem::class);
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
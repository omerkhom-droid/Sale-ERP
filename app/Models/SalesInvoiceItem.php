<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    /*
    |--------------------------------------------------------------------------
    | fillable
    |--------------------------------------------------------------------------
    | حقول أصناف فاتورة البيع.
    */
    protected $fillable = [
        'sales_invoice_id',

        'product_id',
        'product_name',
        'product_sku',

        'product_unit_id',
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

    /*
    |--------------------------------------------------------------------------
    | salesInvoice
    |--------------------------------------------------------------------------
    | السطر يتبع فاتورة بيع.
    */
    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    /*
    |--------------------------------------------------------------------------
    | product
    |--------------------------------------------------------------------------
    | الصنف المباع.
    */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | productUnit
    |--------------------------------------------------------------------------
    | وحدة البيع.
    */
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }


    public function returnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

}
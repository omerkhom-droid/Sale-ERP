<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_invoice_item_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'base_quantity',
        'unit_cost',
        'discount_amount',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    /*
    |--------------------------------------------------------------------------
    | purchaseReturn
    |--------------------------------------------------------------------------
    | هذا السطر يتبع مستند مردود مشتريات.
    */
    // public function purchaseReturn()
    // {
    //     return $this->belongsTo(PurchaseReturn::class);
    // }
    
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
    /*
    |--------------------------------------------------------------------------
    | invoiceItem
    |--------------------------------------------------------------------------
    | هذا السطر مرتبط بسطر الفاتورة الأصلي.
    | منه نعرف الكمية الأصلية والتكلفة الأصلية.
    */
    public function invoiceItem()
    {
        return $this->belongsTo(PurchaseInvoiceItem::class, 'purchase_invoice_item_id');
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
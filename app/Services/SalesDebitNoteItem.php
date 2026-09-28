<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDebitNoteItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['affects_stock' => 'boolean'];
    public function salesDebitNote() { return $this->belongsTo(SalesDebitNote::class); }
    public function salesInvoiceItem() { return $this->belongsTo(SalesInvoiceItem::class); }
    public function product() { return $this->belongsTo(Product::class); }
}

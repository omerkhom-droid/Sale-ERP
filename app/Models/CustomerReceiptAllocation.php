<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReceiptAllocation extends Model
{
    protected $fillable = [
        'customer_receipt_voucher_id',
        'sales_invoice_id',
        'amount',
    ];

    public function voucher()
    {
        return $this->belongsTo(CustomerReceiptVoucher::class, 'customer_receipt_voucher_id');
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
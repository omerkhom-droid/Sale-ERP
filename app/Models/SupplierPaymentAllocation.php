<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentAllocation extends Model
{
    protected $fillable = [
        'supplier_payment_voucher_id',
        'purchase_invoice_id',
        'amount',
    ];

    public function voucher()
    {
        return $this->belongsTo(SupplierPaymentVoucher::class, 'supplier_payment_voucher_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SupplierPaymentAllocation;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'supplier_id',
        'branch_id',
        'cost_center_id',
        'warehouse_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_account_id',
        'payment_status',
        'status',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'notes',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    

    public function supplierPaymentAllocations()
    {
        /*
        |--------------------------------------------------------------------------
        | علاقة فاتورة المشتريات بتوزيعات سندات الصرف
        |--------------------------------------------------------------------------
        | هذه العلاقة معناها:
        | فاتورة المشتريات ممكن يتم سدادها من سند صرف واحد أو أكثر.
        |
        | supplier_payment_allocations يحتوي:
        | - purchase_invoice_id
        | - supplier_payment_voucher_id
        | - amount
        */

        return $this->hasMany(SupplierPaymentAllocation::class, 'purchase_invoice_id');
    }

    public function returns()
    {
        /*
        |--------------------------------------------------------------------------
        | returns
        |--------------------------------------------------------------------------
        | فاتورة المشتريات يمكن أن يكون لها أكثر من مردود مشتريات.
        |
        | مثال:
        | فاتورة PI-001
        | عليها مردود PR-001
        | وعليها مردود ثاني PR-002
        */
        return $this->hasMany(PurchaseReturn::class, 'purchase_invoice_id');
    }

    
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
    

}
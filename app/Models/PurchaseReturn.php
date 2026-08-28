<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    /*
    |--------------------------------------------------------------------------
    | fillable
    |--------------------------------------------------------------------------
    | هذه الحقول مسموح تعبئتها عن طريق create أو update.
    */
    protected $fillable = [
        'return_no',
        'purchase_invoice_id',
        'supplier_id',
        'branch_id',
        'cost_center_id',
        'warehouse_id',
        'return_date',
        'subtotal',
        'vat_amount',
        'total_amount',
        'status',
        'notes',
        'returned_amount',
        'return_status',
        'created_by',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | invoice
    |--------------------------------------------------------------------------
    | المردود تابع لفاتورة مشتريات واحدة.
    */
    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    /*
    |--------------------------------------------------------------------------
    | supplier
    |--------------------------------------------------------------------------
    | المورد صاحب الفاتورة والمردود.
    */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /*
    |--------------------------------------------------------------------------
    | warehouse
    |--------------------------------------------------------------------------
    | المستودع الذي خرجت منه البضاعة المرتجعة.
    */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | items
    |--------------------------------------------------------------------------
    | تفاصيل الأصناف المرتجعة.
    */
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
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

    /*
    |--------------------------------------------------------------------------
    | supplierPaymentAllocations
    |--------------------------------------------------------------------------
    | هذه العلاقة استخدمناها في كشف حساب المورد.
    | إذا كانت موجودة عندك لا تكررها.
    */
    public function supplierPaymentAllocations()
    {
        return $this->hasMany(SupplierPaymentAllocation::class, 'purchase_invoice_id');
    }
    
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
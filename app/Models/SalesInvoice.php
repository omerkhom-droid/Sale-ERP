<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    /*
    |--------------------------------------------------------------------------
    | fillable
    |--------------------------------------------------------------------------
    | الحقول المسموح بتعبئتها عند إنشاء أو تعديل فاتورة البيع.
    */
    protected $fillable = [
        'invoice_no',

        'customer_id',
        'customer_type',
        'customer_name',
        'customer_mobile',
        'customer_tax_number',
        'customer_address',

        'payment_type',
        'payment_method',

        'branch_id',
        'cost_center_id',
        'warehouse_id',
        'invoice_date',

        'subtotal',
        'discount_amount',
        'vat_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'returned_amount',

        'status',
        'payment_status',
        'return_status',

        'notes',

        'created_by',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'invoice_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | customer
    |--------------------------------------------------------------------------
    | العميل المسجل.
    | قد يكون null إذا كانت الفاتورة لعميل نقدي غير محفوظ.
    */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | branch
    |--------------------------------------------------------------------------
    | الفرع الذي صدرت منه الفاتورة.
    */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /*
    |--------------------------------------------------------------------------
    | warehouse
    |--------------------------------------------------------------------------
    | المستودع الذي خرجت منه البضاعة.
    */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | items
    |--------------------------------------------------------------------------
    | أصناف فاتورة البيع.
    */
    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | creator
    |--------------------------------------------------------------------------
    | المستخدم الذي أنشأ الفاتورة.
    */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | postedBy
    |--------------------------------------------------------------------------
    | المستخدم الذي رحل الفاتورة.
    */
    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /*
    |--------------------------------------------------------------------------
    | cancelledBy
    |--------------------------------------------------------------------------
    | المستخدم الذي ألغى الفاتورة.
    */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receiptAllocations()
    {
        return $this->hasMany(CustomerReceiptAllocation::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
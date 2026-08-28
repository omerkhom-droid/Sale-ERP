<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReceiptVoucher extends Model
{
    protected $fillable = [
        'voucher_no',
        'customer_id',
        'branch_id',
        'cost_center_id',
        'receipt_date',
        'payment_method',
        'amount',
        'allocated_amount',
        'unallocated_amount',
        'status',
        'notes',
        'created_by',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function allocations()
    {
        return $this->hasMany(CustomerReceiptAllocation::class);
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

    public function receiptVouchers()
    {
        return $this->hasMany(CustomerReceiptVoucher::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
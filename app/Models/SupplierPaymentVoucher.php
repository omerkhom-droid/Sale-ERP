<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentVoucher extends Model
{
    protected $fillable = [
        'voucher_no',
        'supplier_id',
        'branch_id',
        'cost_center_id',
        'voucher_date',
        'payment_account_id',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function allocations()
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
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

    
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
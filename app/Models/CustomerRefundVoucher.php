<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerRefundVoucher extends Model
{
    protected $fillable = [
        'voucher_no',
        'sales_return_id',
        'customer_id',
        'branch_id',
        'cost_center_id',
        'refund_date',
        'payment_method',
        'amount',
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
        'refund_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
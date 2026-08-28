<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralPaymentVoucher extends Model
{
    protected $fillable = [
        'voucher_no',
        'branch_id',
        'cost_center_id',
        'voucher_date',
        'payment_method',
        'cash_bank_account_id',
        'opposite_account_id',
        'amount',
        'payee_name',
        'notes',
        'status',
        'created_by',
        'posted_by',
        'cancelled_by',
        'posted_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function cashBankAccount()
    {
        return $this->belongsTo(Account::class, 'cash_bank_account_id');
    }

    public function oppositeAccount()
    {
        return $this->belongsTo(Account::class, 'opposite_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

        /*
    |--------------------------------------------------------------------------
    | أسماء بديلة للطباعة
    |--------------------------------------------------------------------------
    | في سند القبض العام:
    | debitAccount  = حساب الصندوق / البنك
    | creditAccount = الحساب المقابل
    */

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_bank_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'opposite_account_id');
    }
}
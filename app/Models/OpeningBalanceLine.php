<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningBalanceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_balance_id',
        'branch_id',
        'cost_center_id',
        'line_type',
        'account_id',
        'customer_id',
        'supplier_id',
        'debit',
        'credit',
        'description',
    ];

    public function openingBalance()
    {
        return $this->belongsTo(OpeningBalance::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualJournalEntryLine extends Model
{
    protected $fillable = [
        'manual_journal_entry_id',
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

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function manualJournalEntry()
    {
        return $this->belongsTo(ManualJournalEntry::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDebitNote extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'note_date' => 'date', 'posted_at' => 'datetime', 'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2', 'total_cost' => 'decimal:2',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function items() { return $this->hasMany(SalesDebitNoteItem::class); }
}

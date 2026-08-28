<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_no',

        'customer_id',
        'customer_type',
        'customer_name',
        'customer_mobile',
        'customer_tax_number',
        'customer_address',

        'branch_id',
        'cost_center_id',
        'warehouse_id',

        'quotation_date',
        'valid_until',

        'subtotal',
        'discount_amount',
        'vat_amount',
        'total_amount',

        'status',

        'converted_sales_invoice_id',
        'converted_at',
        'converted_by',

        'notes',
        'terms',

        'created_by',

        'approved_at',
        'approved_by',

        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'converted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function convertedSalesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'converted_sales_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
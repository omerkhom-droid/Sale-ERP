<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'default_warehouse_id',
        'default_customer_id',
        'tax_rate',
        'auto_print_receipt',
        'receipt_copies',
        'show_product_images',
        'receipt_title',
        'receipt_footer',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'default_warehouse_id' => 'integer',
        'default_customer_id' => 'integer',
        'tax_rate' => 'decimal:2',
        'auto_print_receipt' => 'boolean',
        'receipt_copies' => 'integer',
        'show_product_images' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function defaultCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'default_customer_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'supplier_type',
        'phone',
        'email',
        'tax_number',
        'commercial_register',
        'country_code',
        'state',
        'city',
        'district',
        'street_name',
        'building_number',
        'additional_number',
        'postal_code',
        'address',
        'opening_balance',
        'balance_type',
        'is_active',
    ];
}
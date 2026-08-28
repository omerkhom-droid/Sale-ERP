<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_code',
        'customer_name',
        'customer_type',
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

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function refundVouchers()
    {
        return $this->hasMany(CustomerRefundVoucher::class);
    }

}
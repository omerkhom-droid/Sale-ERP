<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaDevice extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',

        'device_name',
        'egs_serial_number',
        'environment',

        'common_name',
        'organization_identifier',
        'organization_unit_name',
        'organization_name',
        'country_name',

        'invoice_type',
        'registered_address',
        'business_category',

        'private_key',
        'csr',

        'compliance_certificate',
        'compliance_secret',
        'compliance_request_id',

        'production_certificate',
        'production_secret',
        'production_request_id',

        'invoice_counter',
        'previous_invoice_hash',
        'auto_submit',

        'status',
        'created_by',
    ];

    protected $hidden = [
        'private_key',
        'compliance_secret',
        'production_secret',
    ];

    protected $casts = [
        'invoice_counter' => 'integer',
        'invoice_counter' => 'integer',
        'auto_submit' => 'boolean',
    ];

}
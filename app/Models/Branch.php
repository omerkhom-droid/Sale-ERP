<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'company_id',
        'branch_name',
        'preceatage',
        'tax_registration_number',
        'license_type',
        'license_number',
        'country_code',
        'state',
        'city',
        'neighborhood',
        'street_name',
        'additional_street_name',
        'building_number',
        'secondary_number',
        'postal_zone',
        'phone',
        'details',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'preceatage' => 'decimal:2',
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
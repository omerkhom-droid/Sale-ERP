<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopVehicle extends Model
{
    protected $fillable = ['company_id', 'customer_id', 'plate_number', 'vin', 'make', 'model', 'model_year', 'color', 'notes'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function orders() { return $this->hasMany(WorkshopOrder::class, 'vehicle_id'); }
}

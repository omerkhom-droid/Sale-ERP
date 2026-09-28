<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopOrder extends Model
{
    public const STATUSES = [
        'received' => 'استقبال',
        'inspection' => 'فحص',
        'approval' => 'انتظار موافقة',
        'parts' => 'انتظار قطع',
        'in_progress' => 'تنفيذ',
        'quality_check' => 'فحص نهائي',
        'ready' => 'جاهز للتسليم',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = ['company_id', 'branch_id', 'vehicle_id', 'order_number', 'status', 'odometer', 'customer_complaint', 'diagnosis', 'internal_notes', 'technician_id', 'created_by', 'quotation_id', 'sales_invoice_id', 'delivered_at'];
    protected $casts = ['delivered_at' => 'datetime'];

    public function vehicle() { return $this->belongsTo(WorkshopVehicle::class, 'vehicle_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function items() { return $this->hasMany(WorkshopOrderItem::class); }
    public function events() { return $this->hasMany(WorkshopOrderEvent::class)->oldest(); }
    public function attachments() { return $this->hasMany(WorkshopAttachment::class)->latest(); }
}

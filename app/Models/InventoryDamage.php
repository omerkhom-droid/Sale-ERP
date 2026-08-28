<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDamage extends Model
{
    protected $fillable = [
        'damage_no',
        'damage_date',
        'warehouse_id',
        'status',
        'reason',
        'notes',
        'posted_at',
        'cancelled_at',
        'created_by',
        'posted_by',
        'cancelled_by',
    ];

    protected $casts = [
        'damage_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryDamageItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
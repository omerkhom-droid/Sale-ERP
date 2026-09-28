<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopOrderEvent extends Model
{
    protected $fillable = ['from_status', 'to_status', 'note', 'user_id'];
    public function user() { return $this->belongsTo(User::class); }
}

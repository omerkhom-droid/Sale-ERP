<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'level',
        'account_code',
        'account_name_ar',
        'account_name_en',
        'account_type',
        'normal_balance',
        'is_group',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id')
            ->orderBy('account_code');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}
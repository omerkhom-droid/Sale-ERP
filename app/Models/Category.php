<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'level',
        'category_name',
        'description',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class,'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class,'parent_id')
            ->orderBy('category_name');
    }

    public function childrenRecursive()
    {
        return $this->children()
            ->with('childrenRecursive');
    }
}
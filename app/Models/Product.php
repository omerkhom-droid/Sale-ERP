<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'product_name_ar',
        'product_name_en',
        'short_name',
        'keywords',
        'product_type',
        'show_in_pos',
        'pos_sort_order',
        'pos_button_color',
        'description',
        'minimum_quantity',
        'track_inventory',
        'is_active',
    ];

    protected $casts = [
        'show_in_pos' => 'boolean',
        'pos_sort_order' => 'integer',
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function defaultUnit()
    {
        return $this->hasOne(ProductUnit::class)
            ->where('is_default', true);
    }
    
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
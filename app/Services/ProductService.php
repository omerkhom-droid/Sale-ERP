<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductBarcode;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($this->productData($data));

            $this->syncUnits($product, $data['units'] ?? []);

            $this->storeImages($product, $data['images'] ?? []);

            return $product->fresh([
                'category',
                'brand',
                'units.unit',
                'images',
            ]);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($this->productData($data));

            /*
            |--------------------------------------------------------------------------
            | مهم جدًا
            |--------------------------------------------------------------------------
            | لا نحذف وحدات المنتج القديمة لأنها قد تكون مرتبطة بحركات مخزون
            | inventory_transactions.product_unit_id
            |
            | لذلك نحدث الموجود ونضيف الجديد فقط.
            */
            $this->syncUnitsWithoutDeletingUsedUnits($product, $data['units'] ?? []);

            if (! empty($data['images'])) {
                $this->storeImages($product, $data['images']);
            }

            return $product->fresh([
                'category',
                'brand',
                'units.unit',
                'units.barcode',
                'units.barcodes',
                'images',
            ]);
        });
    }

    private function productData(array $data): array
    {
        return [
            'category_id'       => $data['category_id'],
            'brand_id'          => $data['brand_id'] ?? null,
            'sku'               => $data['sku'],
            'product_name_ar'   => $data['product_name_ar'],
            'product_name_en'   => $data['product_name_en'] ?? null,
            'short_name'        => $data['short_name'] ?? null,
            'keywords'          => $data['keywords'] ?? null,
            'product_type'      => $data['product_type'],
            'description'       => $data['description'] ?? null,
            'minimum_quantity'  => $data['minimum_quantity'] ?? 0,
            'track_inventory'   => (bool) ($data['track_inventory'] ?? false),
            'is_active'         => (bool) ($data['is_active'] ?? false),

            /*
            |--------------------------------------------------------------------------
            | POS
            |--------------------------------------------------------------------------
            */
            'show_in_pos'       => (bool) ($data['show_in_pos'] ?? false),
            'pos_sort_order'    => (int) ($data['pos_sort_order'] ?? 0),
            'pos_button_color'  => $data['pos_button_color'] ?? null,
        ];
    }

    private function syncUnits(Product $product, array $units): void
    {
        if (empty($units)) {
            return;
        }

        $hasDefault = collect($units)->contains(function ($unit) {
            return ! empty($unit['is_default']);
        });

        foreach ($units as $index => $unit) {
            if (empty($unit['unit_id'])) {
                continue;
            }

            $productUnit = ProductUnit::create([
                'product_id'         => $product->id,
                'unit_id'            => (int) $unit['unit_id'],
                'factor'             => (float) ($unit['factor'] ?? 1),
                'purchase_price'     => (float) ($unit['purchase_price'] ?? 0),
                'sale_price'         => (float) ($unit['sale_price'] ?? 0),
                'minimum_sale_price' => (float) ($unit['minimum_sale_price'] ?? 0),
                'is_default'         => $hasDefault
                    ? ! empty($unit['is_default'])
                    : $index === 0,
            ]);

            $this->syncBarcode($productUnit, $unit['barcode'] ?? null);
        }

        $this->ensureSingleDefaultUnit($product);
    }

   private function syncUnitsWithoutDeletingUsedUnits(Product $product, array $units): void
{
    if (empty($units)) {
        return;
    }

    $hasDefault = collect($units)->contains(function ($unit) {
        return ! empty($unit['is_default']);
    });

    foreach ($units as $index => $unit) {
        if (empty($unit['unit_id'])) {
            continue;
        }

        $unitId = (int) $unit['unit_id'];

        $payload = [
            'unit_id'            => $unitId,
            'factor'             => (float) ($unit['factor'] ?? 1),
            'purchase_price'     => (float) ($unit['purchase_price'] ?? 0),
            'sale_price'         => (float) ($unit['sale_price'] ?? 0),
            'minimum_sale_price' => (float) ($unit['minimum_sale_price'] ?? 0),
            'is_default'         => $hasDefault
                ? ! empty($unit['is_default'])
                : $index === 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | 1) نحاول نجيب الوحدة بالـ id المرسل من الفورم
        |--------------------------------------------------------------------------
        */
        $productUnit = null;

        if (! empty($unit['id'])) {
            $productUnit = $product->units()
                ->whereKey((int) $unit['id'])
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | 2) لو id لم يصل أو غير صحيح، نبحث بنفس product_id + unit_id
        |--------------------------------------------------------------------------
        | هذا يمنع Duplicate entry product_id + unit_id.
        */
        if (! $productUnit) {
            $productUnit = $product->units()
                ->where('unit_id', $unitId)
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | 3) لو موجودة نحدثها، لو غير موجودة ننشئها
        |--------------------------------------------------------------------------
        */
        if ($productUnit) {
            $productUnit->update($payload);
        } else {
            $productUnit = ProductUnit::create(array_merge($payload, [
                'product_id' => $product->id,
            ]));
        }

        $this->syncBarcode($productUnit, $unit['barcode'] ?? null);
    }

    $this->ensureSingleDefaultUnit($product);
}


    private function syncBarcode(ProductUnit $productUnit, ?string $barcode): void
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            ProductBarcode::query()
                ->where('product_unit_id', $productUnit->id)
                ->delete();

            return;
        }

        ProductBarcode::query()
            ->updateOrCreate(
                [
                    'product_unit_id' => $productUnit->id,
                ],
                [
                    'barcode'    => $barcode,
                    'is_default' => true,
                ]
            );
    }

    private function ensureSingleDefaultUnit(Product $product): void
    {
        /*
        |--------------------------------------------------------------------------
        | ضمان وجود وحدة افتراضية
        |--------------------------------------------------------------------------
        */
        if (! $product->units()->where('is_default', true)->exists()) {
            $firstUnit = $product->units()
                ->orderBy('id')
                ->first();

            if ($firstUnit) {
                $firstUnit->update([
                    'is_default' => true,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ضمان أن هناك وحدة افتراضية واحدة فقط
        |--------------------------------------------------------------------------
        */
        $defaultUnits = $product->units()
            ->where('is_default', true)
            ->orderBy('id')
            ->get();

        if ($defaultUnits->count() <= 1) {
            return;
        }

        $keepDefaultId = $defaultUnits->first()->id;

        $product->units()
            ->where('id', '!=', $keepDefaultId)
            ->update([
                'is_default' => false,
            ]);
    }

    private function storeImages(Product $product, array $images): void
    {
        foreach ($images as $index => $image) {
            if (! $image) {
                continue;
            }

            $path = $image->store('products', 'public');

            ProductImage::create([
                'product_id'  => $product->id,
                'image'       => $path,
                'sort_order'  => $index,
                'is_default'  => $index === 0 && ! $product->images()->exists(),
            ]);
        }
    }
}
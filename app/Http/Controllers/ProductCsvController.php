<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvController extends Controller
{
    private array $headers = [
        'sku',
        'product_name_ar',
        'product_name_en',
        'category_name',
        'brand_name',
        'product_type',
        'short_name',
        'minimum_quantity',
        'track_inventory',
        'is_active',
        'unit_name',
        'factor',
        'purchase_price',
        'sale_price',
        'minimum_sale_price',
        'barcode',
        'is_default_unit',
    ];

    public function index()
    {
        return view('products.csv');
    }

    public function template(): StreamedResponse
    {
        $rows = [
            [
                'RH-001',
                'شمعة يمين كامري',
                'Right Head Light',
                'إنارة',
                'Toyota',
                'stock',
                'شمعة يمين',
                '5',
                '1',
                '1',
                'حبة',
                '1',
                '120',
                '180',
                '160',
                '628100001',
                '1',
            ],
            [
                'RH-001',
                'شمعة يمين كامري',
                'Right Head Light',
                'إنارة',
                'Toyota',
                'stock',
                'شمعة يمين',
                '5',
                '1',
                '1',
                'كرتون',
                '12',
                '1300',
                '1900',
                '1750',
                '628100002',
                '0',
            ],
        ];

        return $this->downloadCsv('products-template.csv', $rows);
    }

    public function export(): StreamedResponse
    {
        $rows = [];

        $products = Product::query()
            ->with(['category', 'brand', 'units.unit'])
            ->orderBy('sku')
            ->get();

        foreach ($products as $product) {
            foreach ($product->units as $productUnit) {
                $barcode = ProductBarcode::query()
                    ->where('product_unit_id', $productUnit->id)
                    ->orderByDesc('is_default')
                    ->value('barcode');

                $rows[] = [
                    $product->sku,
                    $product->product_name_ar,
                    $product->product_name_en,
                    $product->category?->category_name,
                    $product->brand?->brand_name,
                    $product->product_type,
                    $product->short_name,
                    $product->minimum_quantity,
                    $product->track_inventory ? 1 : 0,
                    $product->is_active ? 1 : 0,
                    $productUnit->unit?->unit_name,
                    $productUnit->factor,
                    $productUnit->purchase_price,
                    $productUnit->sale_price,
                    $productUnit->minimum_sale_price,
                    $barcode,
                    $productUnit->is_default ? 1 : 0,
                ];
            }
        }

        return $this->downloadCsv('products-export.csv', $rows);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'file.required' => 'يرجى اختيار ملف CSV.',
            'file.mimes' => 'صيغة الملف يجب أن تكون CSV.',
        ]);

        $path = $request->file('file')->getRealPath();

        $rows = $this->readCsv($path);

        $createdProducts = 0;
        $updatedProducts = 0;
        $createdUnits = 0;
        $updatedUnits = 0;
        $processedBarcodes = 0;
        $errors = [];

        DB::transaction(function () use (
            $rows,
            &$createdProducts,
            &$updatedProducts,
            &$createdUnits,
            &$updatedUnits,
            &$processedBarcodes,
            &$errors
        ) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $sku = $this->clean($row['sku'] ?? null);
                $productNameAr = $this->clean($row['product_name_ar'] ?? null);
                $unitName = $this->clean($row['unit_name'] ?? null);

                if (! $sku) {
                    $errors[] = "السطر {$rowNumber}: كود المنتج sku فارغ.";
                    continue;
                }

                if (! $productNameAr) {
                    $errors[] = "السطر {$rowNumber}: اسم المنتج العربي فارغ.";
                    continue;
                }

                if (! $unitName) {
                    $errors[] = "السطر {$rowNumber}: اسم الوحدة فارغ.";
                    continue;
                }

                $category = $this->findOrCreateCategory($row['category_name'] ?? null);
                $brand = $this->findOrCreateBrand($row['brand_name'] ?? null);
                $unit = $this->findOrCreateUnit($unitName);

                $product = Product::query()
                    ->where('sku', $sku)
                    ->first();

                $productData = [
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'sku' => $sku,
                    'product_name_ar' => $productNameAr,
                    'product_name_en' => $this->clean($row['product_name_en'] ?? null),
                    'short_name' => $this->clean($row['short_name'] ?? null),
                    'product_type' => $this->clean($row['product_type'] ?? null) ?: 'stock',
                    'minimum_quantity' => $this->toFloat($row['minimum_quantity'] ?? 0),
                    'track_inventory' => $this->toBool($row['track_inventory'] ?? 1),
                    'is_active' => $this->toBool($row['is_active'] ?? 1),
                ];

                if (! $product) {
                    $product = Product::create($productData);
                    $createdProducts++;
                } else {
                    $product->update($productData);
                    $updatedProducts++;
                }

                $hasUnitsBefore = ProductUnit::query()
                    ->where('product_id', $product->id)
                    ->exists();

                $productUnit = ProductUnit::query()
                    ->where('product_id', $product->id)
                    ->where('unit_id', $unit->id)
                    ->first();

                $isDefault = $this->toBool($row['is_default_unit'] ?? 0);

                if (! $hasUnitsBefore) {
                    $isDefault = true;
                }

                if ($isDefault) {
                    ProductUnit::query()
                        ->where('product_id', $product->id)
                        ->update(['is_default' => false]);
                }

                $unitData = [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'factor' => $this->toFloat($row['factor'] ?? 1) ?: 1,
                    'purchase_price' => $this->toFloat($row['purchase_price'] ?? 0),
                    'sale_price' => $this->toFloat($row['sale_price'] ?? 0),
                    'minimum_sale_price' => $this->toFloat($row['minimum_sale_price'] ?? 0),
                    'is_default' => $isDefault,
                ];

                if (! $productUnit) {
                    $productUnit = ProductUnit::create($unitData);
                    $createdUnits++;
                } else {
                    $productUnit->update($unitData);
                    $updatedUnits++;
                }

                $barcode = $this->clean($row['barcode'] ?? null);

                if ($barcode) {
                    $barcodeUsedElsewhere = ProductBarcode::query()
                        ->where('barcode', $barcode)
                        ->where('product_unit_id', '!=', $productUnit->id)
                        ->exists();

                    if ($barcodeUsedElsewhere) {
                        $errors[] = "السطر {$rowNumber}: الباركود {$barcode} مستخدم مع منتج أو وحدة أخرى.";
                        continue;
                    }

                    ProductBarcode::updateOrCreate(
                        [
                            'product_unit_id' => $productUnit->id,
                            'barcode' => $barcode,
                        ],
                        [
                            'is_default' => true,
                        ]
                    );

                    $processedBarcodes++;
                }
            }
        });

        return back()->with([
            'success' => 'تمت معالجة ملف المنتجات بنجاح.',
            'import_summary' => [
                'created_products' => $createdProducts,
                'updated_products' => $updatedProducts,
                'created_units' => $createdUnits,
                'updated_units' => $updatedUnits,
                'barcodes' => $processedBarcodes,
                'errors' => $errors,
            ],
        ]);
    }

    private function downloadCsv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM حتى تظهر العربية بشكل صحيح في Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $this->headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value) {
            return trim(str_replace("\xEF\xBB\xBF", '', (string) $value));
        }, $header);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data)) === 0) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $key) {
                $row[$key] = $data[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function findOrCreateCategory($value): ?Category
    {
        $name = $this->clean($value);

        if (! $name) {
            return null;
        }

        return Category::firstOrCreate(
            ['category_name' => $name],
            ['is_active' => true]
        );
    }

    private function findOrCreateBrand($value): ?Brand
    {
        $name = $this->clean($value);

        if (! $name) {
            return null;
        }

        return Brand::firstOrCreate(
            ['brand_name' => $name],
            ['is_active' => true]
        );
    }

    private function findOrCreateUnit(string $unitName): Unit
    {
        return Unit::firstOrCreate(
            ['unit_name' => $unitName],
            ['is_active' => true]
        );
    }

    private function clean($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    private function toBool($value): bool
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'نعم'], true);
    }
}
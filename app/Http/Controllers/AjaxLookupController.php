<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxLookupController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        $query = Product::query()
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_name_ar', 'like', "%{$search}%")
                    ->orWhere('product_name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereExists(function ($subQuery) use ($search) {
                        $subQuery->select(DB::raw(1))
                            ->from('product_barcodes')
                            ->join('product_units', 'product_units.id', '=', 'product_barcodes.product_unit_id')
                            ->whereColumn('product_units.product_id', 'products.id')
                            ->where('product_barcodes.barcode', 'like', "%{$search}%");
                    });
            });
        }

        $products = $query
            ->orderBy('product_name_ar')
            ->paginate(20);

        return response()->json([
            'results' => $products->getCollection()->map(function ($product) {
                $name = $product->product_name_ar
                    ?? $product->product_name_en
                    ?? ('صنف #' . $product->id);

                $code = $product->sku ?? '';

                return [
                    'id' => $product->id,
                    'text' => trim(($code ? $code . ' - ' : '') . $name),
                ];
            })->values(),
            'pagination' => [
                'more' => $products->hasMorePages(),
            ],
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        $query = Customer::query()
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%")
                    ->orWhere('commercial_register', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderBy('customer_name')
            ->paginate(20);

        return response()->json([
            'results' => $customers->getCollection()->map(function ($customer) {
                $name = $customer->customer_name
                    ?? $customer->name
                    ?? ('عميل #' . $customer->id);

                return [
                    'id' => $customer->id,
                    'text' => $name,
                ];
            })->values(),
            'pagination' => [
                'more' => $customers->hasMorePages(),
            ],
        ]);
    }

    public function suppliers(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        $query = Supplier::query()
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%")
                    ->orWhere('commercial_register', 'like', "%{$search}%");
            });
        }

        $suppliers = $query
            ->orderBy('supplier_name')
            ->paginate(20);

        return response()->json([
            'results' => $suppliers->getCollection()->map(function ($supplier) {
                $name = $supplier->supplier_name
                    ?? $supplier->name
                    ?? ('مورد #' . $supplier->id);

                return [
                    'id' => $supplier->id,
                    'text' => $name,
                ];
            })->values(),
            'pagination' => [
                'more' => $suppliers->hasMorePages(),
            ],
        ]);
    }

public function productByBarcode(Request $request): JsonResponse
{
    $barcode = trim((string) $request->get('barcode', ''));

    if ($barcode === '') {
        return response()->json([
            'message' => 'يرجى إدخال الباركود أو كود الصنف.',
        ], 422);
    }

    $selectedProductUnitId = null;

    $barcodeRecord = ProductBarcode::query()
        ->select([
            'product_barcodes.*',
            'product_units.product_id',
        ])
        ->join('product_units', 'product_units.id', '=', 'product_barcodes.product_unit_id')
        ->where('product_barcodes.barcode', $barcode)
        ->first();

    if ($barcodeRecord) {
        $selectedProductUnitId = (int) $barcodeRecord->product_unit_id;

        $product = Product::query()
            ->where('is_active', true)
            ->whereKey($barcodeRecord->product_id)
            ->first();
    } else {
        $product = Product::query()
            ->where('is_active', true)
            ->where('sku', $barcode)
            ->first();
    }

    if (! $product) {
        return response()->json([
            'message' => 'لم يتم العثور على صنف بهذا الباركود أو الكود.',
        ], 404);
    }

    $productUnit = null;

    if ($selectedProductUnitId) {
        $productUnit = ProductUnit::query()
            ->where('product_id', $product->id)
            ->whereKey($selectedProductUnitId)
            ->first();
    }

    if (! $productUnit) {
        $productUnit = ProductUnit::query()
            ->where('product_id', $product->id)
            ->orderByDesc('is_default')
            ->first();
    }

    if (! $productUnit) {
        return response()->json([
            'message' => 'الصنف موجود لكن لا توجد وحدة مرتبطة به.',
        ], 422);
    }

    $name = $product->product_name_ar
        ?? $product->product_name_en
        ?? ('صنف #' . $product->id);

    $code = $product->sku ?? '';

    return response()->json([
        'id' => $product->id,
        'text' => trim(($code ? $code . ' - ' : '') . $name),
        'product_unit_id' => $productUnit->id,
        'sale_price' => (float) $productUnit->sale_price,
        'minimum_sale_price' => (float) $productUnit->minimum_sale_price,
    ]);
}

}
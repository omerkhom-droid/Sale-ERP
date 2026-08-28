<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Unit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    private function actorIsSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) ($user->is_super_admin ?? false)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || $user->hasRole('Super Admin');
    }

    private function applyCompanyScope($query, string $table)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (
            ! $this->actorIsSuperAdmin()
            && Schema::hasColumn($table, 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertModelCompanyAccess($model, string $table, string $message): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn($table, 'company_id')
            && ! empty($user->company_id)
            && (int) $model->company_id !== (int) $user->company_id
        ) {
            abort(403, $message);
        }
    }

    private function assertProductAccess(Product $product): void
    {
        $this->assertModelCompanyAccess(
            $product,
            'products',
            'لا تملك صلاحية الوصول إلى هذا الصنف.'
        );
    }

    private function activeCategories()
    {
        return $this->applyCompanyScope(
                Category::where('is_active', true),
                'categories'
            )
            ->orderBy('category_name')
            ->get();
    }

    private function activeBrands()
    {
        return $this->applyCompanyScope(
                Brand::where('is_active', true),
                'brands'
            )
            ->orderBy('brand_name')
            ->get();
    }

    private function activeUnits()
    {
        return $this->applyCompanyScope(
                Unit::where('is_active', true),
                'units'
            )
            ->orderBy('unit_name')
            ->get();
    }

    private function assertProductReferencesAllowed(array $data): void
    {
        $user = auth()->user();

        if (! $user || $this->actorIsSuperAdmin() || empty($user->company_id)) {
            return;
        }

        if (
            Schema::hasColumn('categories', 'company_id')
            && ! empty($data['category_id'])
            && ! Category::where('id', $data['category_id'])
                ->where('company_id', $user->company_id)
                ->exists()
        ) {
            abort(403, 'لا تملك صلاحية استخدام هذا التصنيف.');
        }

        if (
            Schema::hasColumn('brands', 'company_id')
            && ! empty($data['brand_id'])
            && ! Brand::where('id', $data['brand_id'])
                ->where('company_id', $user->company_id)
                ->exists()
        ) {
            abort(403, 'لا تملك صلاحية استخدام هذه العلامة التجارية.');
        }

        if (Schema::hasColumn('units', 'company_id')) {
            $unitIds = collect($data['units'] ?? [])
                ->pluck('unit_id')
                ->filter()
                ->unique()
                ->values();

            if ($unitIds->isNotEmpty()) {
                $allowedUnitsCount = Unit::whereIn('id', $unitIds)
                    ->where('company_id', $user->company_id)
                    ->count();

                if ($allowedUnitsCount !== $unitIds->count()) {
                    abort(403, 'لا تملك صلاحية استخدام إحدى الوحدات المحددة.');
                }
            }
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $units = $this->activeUnits();

        return view('products.index', compact('categories', 'brands', 'units'));
    }

    public function fetch()
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $user = auth()->user();

        $products = $this->applyCompanyScope(
                Product::with([
                    'category',
                    'brand',
                    'defaultUnit.unit',
                ]),
                'products'
            )
            ->latest();

        return DataTables::of($products)
            ->addIndexColumn()

            ->addColumn('category_name', function ($row) {
                return e($row->category?->category_name ?? '-');
            })

            ->addColumn('brand_name', function ($row) {
                return e($row->brand?->brand_name ?? '-');
            })

            ->addColumn('default_unit', function ($row) {
                return e($row->defaultUnit?->unit?->unit_name ?? '-');
            })

            ->addColumn('sale_price', function ($row) {
                return number_format(
                    (float) ($row->defaultUnit?->sale_price ?? 0),
                    2
                );
            })

            ->addColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('products.edit')) {
                    $buttons .= '
                        <a href="' . route('products.edit', $row) . '"
                           class="btn btn-primary btn-sm">
                            تعديل
                        </a>
                    ';
                }

                if ($user && $user->can('products.delete')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-danger btn-sm deleteBtn"
                                data-id="' . $row->id . '">
                            حذف
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex justify-content-center gap-1 flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns([
                'status',
                'actions',
            ])

            ->make(true);
    }

    public function create()
    {
        abort_unless(auth()->user()?->can('products.create'), 403);

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $units = $this->activeUnits();

        return view('products.create', compact(
            'categories',
            'brands',
            'units'
        ));
    }

    public function store(
        StoreProductRequest $request,
        ProductService $service
    ) {
        abort_unless(auth()->user()?->can('products.create'), 403);

        try {
            $data = $request->validated();

            $this->assertProductReferencesAllowed($data);

            if (
                Schema::hasColumn('products', 'company_id')
                && ! empty(auth()->user()?->company_id)
            ) {
                $data['company_id'] = auth()->user()->company_id;
            }

            $service->store($data);

            return response()->json([
                'status' => true,
                'message' => 'تم حفظ الصنف بنجاح',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit(Product $product)
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);

        $this->assertProductAccess($product);

        $product->load([
            'units.barcode',
            'images',
        ]);

        $categories = $this->activeCategories();
        $brands = $this->activeBrands();
        $units = $this->activeUnits();

        return view('products.edit', compact(
            'product',
            'categories',
            'brands',
            'units'
        ));
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductService $service
    ) {
        abort_unless(auth()->user()?->can('products.edit'), 403);

        $this->assertProductAccess($product);

        $data = $request->validated();

        $this->assertProductReferencesAllowed($data);

        unset($data['company_id']);

        $service->update($product, $data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل الصنف بنجاح',
        ]);
    }

    public function destroy(Product $product)
    {
        abort_unless(auth()->user()?->can('products.delete'), 403);

        $this->assertProductAccess($product);

        DB::transaction(function () use ($product) {
            $product->loadMissing('images');

            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image);
            }

            $product->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الصنف بنجاح',
        ]);
    }

    public function deleteImage(ProductImage $image)
    {
        abort_unless(auth()->user()?->can('products.edit'), 403);

        $product = null;

        if (method_exists($image, 'product')) {
            $product = $image->product;
        }

        if (! $product && ! empty($image->product_id)) {
            $product = Product::find($image->product_id);
        }

        abort_unless($product, 404);

        $this->assertProductAccess($product);

        Storage::disk('public')->delete($image->image);

        $image->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الصورة بنجاح',
        ]);
    }
}
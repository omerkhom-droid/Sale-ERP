<?php

namespace App\Http\Controllers;

use App\Models\AccountSetting;
use App\Models\Brand;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Warehouse;
use App\Services\JournalEntryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCountController extends Controller
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {
    }

    private function actorUserType(): string
    {
        return auth()->user()?->user_type ?? 'user';
    }

    private function actorCanSeeAllCompanies(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
        ], true);
    }

    private function actorBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        if (in_array($this->actorUserType(), ['company_owner', 'company_admin'], true)) {
            if (! $user->company_id) {
                return [];
            }

            return Branch::query()
                ->where('company_id', $user->company_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        if (! $user->branch_id) {
            return [];
        }

        return [(int) $user->branch_id];
    }

    private function applyWarehouseScope($query, bool $onlyActive = false)
    {
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    private function activeWarehouses()
    {
        return $this->applyWarehouseScope(Warehouse::query(), true)
            ->orderBy('warehouse_name')
            ->get();
    }

    private function assertWarehouseAllowed(?int $warehouseId): Warehouse
    {
        abort_unless($warehouseId, 403, 'المستودع غير صحيح.');

        $query = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true);

        $this->applyWarehouseScope($query);

        $warehouse = $query->first();

        abort_unless(
            $warehouse,
            403,
            'لا تملك صلاحية استخدام هذا المستودع أو أن المستودع غير نشط.'
        );

        return $warehouse;
    }

    private function applyCountScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('warehouse', function ($warehouseQuery) use ($branchIds) {
            $warehouseQuery->whereIn('branch_id', $branchIds);
        });
    }

    private function assertCountAccess(InventoryCount $inventoryCount): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        if (empty($branchIds)) {
            abort(403, 'لا تملك صلاحية الوصول إلى سند الجرد.');
        }

        $inventoryCount->loadMissing('warehouse');

        abort_unless(
            in_array((int) $inventoryCount->warehouse?->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى جرد من فرع آخر.'
        );
    }


    public function index()
    {
        $query = InventoryCount::query()
            ->with(['warehouse', 'creator']);

        $this->applyCountScope($query);

        $counts = $query
            ->latest()
            ->paginate(20);

        return view('inventory-counts.index', compact('counts'));
    }

    public function create()
    {
        $warehouses = $this->activeWarehouses();

        $categories = Category::query()
            ->orderBy('category_name')
            ->get();

        $brands = Brand::query()
            ->orderBy('brand_name')
            ->get();

        return view('inventory-counts.create', compact(
            'warehouses',
            'categories',
            'brands'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'count_date' => ['required', 'date'],
            'scope_type' => ['required', 'in:all,category,brand'],
            'scope_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['scope_type'] !== 'all' && empty($data['scope_id'])) {
            return back()
                ->withErrors(['scope_id' => 'يرجى اختيار النطاق.'])
                ->withInput();
        }

        $this->assertWarehouseAllowed((int) $data['warehouse_id']);

        $inventoryCount = DB::transaction(function () use ($data) {
            $count = InventoryCount::create([
                'count_no' => $this->generateCountNo(),
                'warehouse_id' => $data['warehouse_id'],
                'count_date' => $data['count_date'],
                'scope_type' => $data['scope_type'],
                'scope_id' => $data['scope_type'] === 'all' ? null : $data['scope_id'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $productsQuery = Product::query()
                ->with([
                    'units' => function ($query) {
                        $query->with('unit')
                            ->orderByDesc('is_default')
                            ->orderBy('id');
                    },
                ])
                ->where('is_active', true)
                ->where('track_inventory', true);

            if ($data['scope_type'] === 'category') {
                $productsQuery->where('category_id', $data['scope_id']);
            }

            if ($data['scope_type'] === 'brand') {
                $productsQuery->where('brand_id', $data['scope_id']);
            }

            $products = $productsQuery
                ->orderBy('product_name_ar')
                ->get();

            foreach ($products as $product) {
                $productUnit = $product->units->first();

                if (! $productUnit) {
                    continue;
                }

                $stock = ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                $baseSystemQuantity = (float) ($stock?->quantity ?? 0);
                $unitCost = (float) ($stock?->average_cost ?? 0);

                $systemQuantity = $this->fromBaseQuantity(
                    productUnit: $productUnit,
                    baseQuantity: $baseSystemQuantity
                );

                InventoryCountItem::create([
                    'inventory_count_id' => $count->id,
                    'product_id' => $product->id,
                    'product_unit_id' => $productUnit->id,
                    'system_quantity' => $systemQuantity,
                    'actual_quantity' => null,
                    'variance_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'total_variance_cost' => 0,
                ]);
            }

            return $count;
        });

        return redirect()
            ->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'تم إنشاء الجرد بنجاح. أدخل الكميات الفعلية ثم رحّل الجرد.');
    }

    public function show(InventoryCount $inventoryCount)
    {
        $this->assertCountAccess($inventoryCount);

        $inventoryCount->load([
            'warehouse',
            'items.product',
            'items.productUnit.unit',
        ]);

        return view('inventory-counts.show', compact('inventoryCount'));
    }

    public function updateItems(Request $request, InventoryCount $inventoryCount)
    {
        $this->assertCountAccess($inventoryCount);

        if (! $inventoryCount->isDraft()) {
            return back()->withErrors([
                'error' => 'لا يمكن تعديل جرد غير مسودة.',
            ]);
        }

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.actual_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $inventoryCount) {
            foreach ($data['items'] as $itemId => $itemData) {
                $item = $inventoryCount->items()
                    ->with('productUnit')
                    ->whereKey($itemId)
                    ->first();

                if (! $item) {
                    continue;
                }

                $actualQuantity = $itemData['actual_quantity'];

                if ($actualQuantity === null || $actualQuantity === '') {
                    $systemQuantity = (float) $item->system_quantity;

                    if ($systemQuantity == 0.0) {
                        $actualQuantity = 0;
                    } else {
                        $item->update([
                            'actual_quantity' => null,
                            'variance_quantity' => 0,
                            'total_variance_cost' => 0,
                            'notes' => $itemData['notes'] ?? null,
                        ]);

                        continue;
                    }
                }

                $actualQuantity = (float) $actualQuantity;
                $systemQuantity = (float) $item->system_quantity;
                $variance = round($actualQuantity - $systemQuantity, 3);

                $baseVariance = $this->getBaseQuantity(
                    productUnit: $item->productUnit,
                    quantity: $variance
                );

                $unitCost = $this->averageCost(
                    productId: (int) $item->product_id,
                    warehouseId: (int) $inventoryCount->warehouse_id
                );

                $item->update([
                    'actual_quantity' => $actualQuantity,
                    'variance_quantity' => $variance,
                    'unit_cost' => $unitCost,
                    'total_variance_cost' => round($baseVariance * $unitCost, 2),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'تم حفظ كميات الجرد.');
    }


    public function post(InventoryCount $inventoryCount)
    {
        $this->assertCountAccess($inventoryCount);

        if (! $inventoryCount->isDraft()) {
            return back()->withErrors([
                'error' => 'لا يمكن ترحيل هذا الجرد.',
            ]);
        }

        $inventoryCount->load('items');

        $notCounted = $inventoryCount->items()
            ->whereNull('actual_quantity')
            ->where('system_quantity', '!=', 0)
            ->count();

        if ($notCounted > 0) {
            return back()->withErrors([
                'error' => "يوجد {$notCounted} صنف لم يتم إدخال كميته الفعلية.",
            ]);
        }

        $inventoryCount->items()
            ->whereNull('actual_quantity')
            ->where('system_quantity', 0)
            ->update([
                'actual_quantity' => 0,
                'variance_quantity' => 0,
                'total_variance_cost' => 0,
            ]);

        try {
            DB::transaction(function () use ($inventoryCount) {
                $inventoryCount = InventoryCount::query()
                    ->with([
                        'warehouse',
                        'items.product',
                        'items.productUnit.unit',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($inventoryCount->id);

                foreach ($inventoryCount->items as $item) {
                    $variance = (float) $item->variance_quantity;

                    if ($variance == 0.0) {
                        continue;
                    }

                    $baseVariance = $this->getBaseQuantity(
                        productUnit: $item->productUnit,
                        quantity: $variance
                    );

                    $this->applyInventoryCountVariance(
                        count: $inventoryCount,
                        item: $item,
                        baseVariance: $baseVariance
                    );
                }

                $this->createJournalEntryForInventoryCount($inventoryCount);

                $inventoryCount->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                ]);
            });

        } catch (Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'تم ترحيل الجرد وتحديث المخزون والقيود المحاسبية.');
    }


    public function cancel(InventoryCount $inventoryCount)
    {
        $this->assertCountAccess($inventoryCount);

        if (! $inventoryCount->isPosted()) {
            return back()->withErrors([
                'error' => 'لا يمكن إلغاء إلا الجرد المرحل.',
            ]);
        }

        try {
            DB::transaction(function () use ($inventoryCount) {
                $inventoryCount = InventoryCount::query()
                    ->with([
                        'warehouse',
                        'items.product',
                        'items.productUnit.unit',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($inventoryCount->id);

                foreach ($inventoryCount->items as $item) {
                    $variance = (float) $item->variance_quantity;

                    if ($variance == 0.0) {
                        continue;
                    }

                    $baseVariance = $this->getBaseQuantity(
                        productUnit: $item->productUnit,
                        quantity: $variance
                    );

                    $reverseBaseVariance = -1 * $baseVariance;

                    $this->applyInventoryCountVariance(
                        count: $inventoryCount,
                        item: $item,
                        baseVariance: $reverseBaseVariance,
                        isCancel: true
                    );
                }

                $this->reverseJournalEntriesForInventoryCount($inventoryCount);

                $inventoryCount->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                ]);
            });

        } catch (Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'تم إلغاء الجرد وإنشاء حركة عكسية وقيد عكسي.');
    }

    public function print(InventoryCount $inventoryCount)
    {
        $this->assertCountAccess($inventoryCount);

        $inventoryCount->load([
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('inventory-counts.print', compact('inventoryCount'));
    }
    
    private function getBaseQuantity(ProductUnit $productUnit, float $quantity): float
    {
        $factor = (float) (
            $productUnit->getAttribute('conversion_factor')
            ?? $productUnit->getAttribute('factor')
            ?? 1
        );

        if ($factor <= 0) {
            $factor = 1;
        }

        return round($quantity * $factor, 3);
    }

    private function fromBaseQuantity(ProductUnit $productUnit, float $baseQuantity): float
    {
        $factor = (float) (
            $productUnit->getAttribute('conversion_factor')
            ?? $productUnit->getAttribute('factor')
            ?? 1
        );

        if ($factor <= 0) {
            $factor = 1;
        }

        return round($baseQuantity / $factor, 3);
    }

    private function averageCost(int $productId, int $warehouseId): float
    {
        return (float) ProductStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('average_cost');
    }

    private function applyInventoryCountVariance(
        InventoryCount $count,
        InventoryCountItem $item,
        float $baseVariance,
        bool $isCancel = false
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $count->warehouse_id)
            ->lockForUpdate()
            ->first();

        $unitCost = (float) ($item->unit_cost ?? 0);

        if (! $stock) {
            if ($baseVariance < 0) {
                throw new Exception('لا يوجد رصيد للصنف في المستودع.');
            }

            $stock = new ProductStock();
            $stock->product_id = $item->product_id;
            $stock->warehouse_id = $count->warehouse_id;
            $stock->quantity = 0;
            $stock->average_cost = $unitCost;
            $stock->save();
        }

        $balanceBefore = (float) $stock->quantity;
        $oldAverageCost = (float) ($stock->average_cost ?? 0);

        if ($unitCost <= 0) {
            $unitCost = $oldAverageCost;
        }

        $balanceAfter = round($balanceBefore + $baseVariance, 3);

        if ($balanceAfter < 0) {
            throw new Exception('الرصيد لا يكفي لتطبيق فرق الجرد.');
        }

        if ($baseVariance > 0) {
            $newAverageCost = $balanceAfter > 0
                ? round((($balanceBefore * $oldAverageCost) + ($baseVariance * $unitCost)) / $balanceAfter, 4)
                : $unitCost;
        } else {
            $newAverageCost = $oldAverageCost;
        }

        $stock->update([
            'quantity' => $balanceAfter,
            'average_cost' => $newAverageCost,
        ]);

        $totalCost = round($baseVariance * $unitCost, 2);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo($isCancel ? 'IC-CAN' : 'IC'),
            'product_id' => $item->product_id,
            'warehouse_id' => $count->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'inventory_count',
            'quantity' => $baseVariance,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => InventoryCount::class,
            'reference_id' => $count->id,
            'notes' => ($isCancel ? 'إلغاء ' : 'ترحيل ') . 'جرد مخزني رقم: ' . $count->count_no,
            'created_by' => auth()->id(),
        ]);

        $item->update([
            'unit_cost' => $unitCost,
            'total_variance_cost' => round(
                $this->getBaseQuantity($item->productUnit, (float) $item->variance_quantity) * $unitCost,
                2
            ),
        ]);
    }

    private function createJournalEntryForInventoryCount(InventoryCount $count): void
    {
        $count->loadMissing([
            'warehouse',
            'items',
        ]);

        $shortageTotal = 0;
        $surplusTotal = 0;

        foreach ($count->items as $item) {
            $amount = round((float) $item->total_variance_cost, 2);

            if ($amount < 0) {
                $shortageTotal += abs($amount);
            }

            if ($amount > 0) {
                $surplusTotal += $amount;
            }
        }

        $shortageTotal = round($shortageTotal, 2);
        $surplusTotal = round($surplusTotal, 2);

        if ($shortageTotal <= 0 && $surplusTotal <= 0) {
            return;
        }

        $inventoryAccount = $this->accountId('inventory_account');
        $shortageAccount = $this->accountId('inventory_count_shortage_expense_account');
        $surplusAccount = $this->accountId('inventory_count_surplus_income_account');

        $branchId = $count->warehouse?->branch_id;

        $lines = [];

        if ($shortageTotal > 0) {
            $lines[] = [
                'account_id' => $shortageAccount,
                'debit' => $shortageTotal,
                'credit' => 0,
                'description' => 'عجز جرد مخزني رقم ' . $count->count_no,
                'customer_id' => null,
                'branch_id' => $branchId,
                'cost_center_id' => null,
            ];

            $lines[] = [
                'account_id' => $inventoryAccount,
                'debit' => 0,
                'credit' => $shortageTotal,
                'description' => 'تخفيض مخزون بسبب عجز جرد رقم ' . $count->count_no,
                'customer_id' => null,
                'branch_id' => $branchId,
                'cost_center_id' => null,
            ];
        }

        if ($surplusTotal > 0) {
            $lines[] = [
                'account_id' => $inventoryAccount,
                'debit' => $surplusTotal,
                'credit' => 0,
                'description' => 'زيادة مخزون بسبب جرد رقم ' . $count->count_no,
                'customer_id' => null,
                'branch_id' => $branchId,
                'cost_center_id' => null,
            ];

            $lines[] = [
                'account_id' => $surplusAccount,
                'debit' => 0,
                'credit' => $surplusTotal,
                'description' => 'زيادة جرد مخزني رقم ' . $count->count_no,
                'customer_id' => null,
                'branch_id' => $branchId,
                'cost_center_id' => null,
            ];
        }

        $this->journalEntryService->create([
            'entry_date' => $count->count_date,
            'document_type' => 'inventory_count',
            'document_number' => $count->count_no,
            'reference_type' => InventoryCount::class,
            'reference_id' => $count->id,
            'description' => 'قيد فروقات جرد مخزني رقم ' . $count->count_no,
            'created_by' => auth()->id(),
            'lines' => $lines,
        ]);
    }

    private function reverseJournalEntriesForInventoryCount(InventoryCount $count): void
    {
        $journalEntries = JournalEntry::query()
            ->where('reference_type', InventoryCount::class)
            ->where('reference_id', $count->id)
            ->get();

        foreach ($journalEntries as $entry) {
            $this->journalEntryService->reverse($entry);
        }
    }

    private function accountId(string $key): int
    {
        $setting = AccountSetting::query()
            ->where('setting_key', $key)
            ->first();

        if (! $setting || ! $setting->account_id) {
            throw new Exception('يرجى ضبط الحساب المحاسبي: ' . $key);
        }

        return (int) $setting->account_id;
    }

    private function generateCountNo(): string
    {
        $prefix = 'IC-' . now()->format('Ymd') . '-';

        $lastId = (int) InventoryCount::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateTransactionNo(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
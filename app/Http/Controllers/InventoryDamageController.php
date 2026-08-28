<?php

namespace App\Http\Controllers;
use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;

use App\Models\Branch;
use App\Models\InventoryDamage;
use App\Models\InventoryDamageItem;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryDamageController extends Controller
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

    private function applyDamageScope($query)
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

    private function assertDamageAccess(InventoryDamage $inventoryDamage): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        if (empty($branchIds)) {
            abort(403, 'لا تملك صلاحية الوصول إلى سند التالف.');
        }

        $inventoryDamage->loadMissing('warehouse');

        abort_unless(
            in_array((int) $inventoryDamage->warehouse?->branch_id, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى سند تالف من فرع آخر.'
        );
    }

    public function index()
    {
        $query = InventoryDamage::query()
            ->with(['warehouse', 'creator']);

        $this->applyDamageScope($query);

        $damages = $query
            ->latest()
            ->paginate(20);

        return view('inventory-damages.index', compact('damages'));
    }

    public function create()
    {
        $warehouses = $this->activeWarehouses();

        return view('inventory-damages.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'damage_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ], [
            'items.required' => 'يرجى إضافة صنف واحد على الأقل.',
            'items.min' => 'يرجى إضافة صنف واحد على الأقل.',
        ]);

        $this->assertWarehouseAllowed((int) $data['warehouse_id']);

        $damage = DB::transaction(function () use ($data) {
            $damage = InventoryDamage::create([
                'damage_no' => $this->generateDamageNo(),
                'damage_date' => $data['damage_date'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'draft',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $productUnit = ProductUnit::query()
                    ->whereKey($item['product_unit_id'])
                    ->where('product_id', $item['product_id'])
                    ->firstOrFail();

                $quantity = (float) $item['quantity'];
                $baseQuantity = $this->getBaseQuantity($productUnit, $quantity);

                $stock = ProductStock::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                $unitCost = (float) ($stock?->average_cost ?? 0);

                InventoryDamageItem::create([
                    'inventory_damage_id' => $damage->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($baseQuantity * $unitCost, 2),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $damage;
        });

        return redirect()
            ->route('inventory-damages.show', $damage)
            ->with('success', 'تم إنشاء سند التالف كمسودة.');
    }

    public function show(InventoryDamage $inventoryDamage)
    {
        $this->assertDamageAccess($inventoryDamage);

        $inventoryDamage->load([
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('inventory-damages.show', compact('inventoryDamage'));
    }

    public function post(InventoryDamage $inventoryDamage)
    {
        $this->assertDamageAccess($inventoryDamage);

        if (! $inventoryDamage->isDraft()) {
            return back()->withErrors([
                'error' => 'لا يمكن ترحيل سند غير مسودة.',
            ]);
        }

        $inventoryDamage->load([
            'items.product',
            'items.productUnit.unit',
        ]);

        if ($inventoryDamage->items->isEmpty()) {
            return back()->withErrors([
                'error' => 'لا يمكن ترحيل سند تالف بدون أصناف.',
            ]);
        }

        $errors = [];

        foreach ($inventoryDamage->items as $item) {
            $baseQuantity = (float) ($item->base_quantity ?? 0);

            if ($baseQuantity <= 0) {
                $baseQuantity = $this->getBaseQuantity(
                    $item->productUnit,
                    (float) $item->quantity
                );
            }

            $stock = ProductStock::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $inventoryDamage->warehouse_id)
                ->first();

            $availableQuantity = (float) ($stock?->quantity ?? 0);

            if ($availableQuantity < $baseQuantity) {
                $productName = $item->product?->product_name_ar
                    ?? $item->product?->product_name_en
                    ?? ('صنف #' . $item->product_id);

                $errors[] = 'الكمية غير متوفرة للصنف: '
                    . $productName
                    . '. المتاح: '
                    . number_format($availableQuantity, 3)
                    . '، المطلوب إتلافه: '
                    . number_format($baseQuantity, 3);
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors);
        }

        try {
            DB::transaction(function () use ($inventoryDamage) {
                $inventoryDamage = InventoryDamage::query()
                ->with([
                    'warehouse',
                    'items.product',
                    'items.productUnit.unit',
                ])
                ->lockForUpdate()
                ->findOrFail($inventoryDamage->id);

                foreach ($inventoryDamage->items as $item) {
                    $baseQuantity = (float) ($item->base_quantity ?? 0);

                    if ($baseQuantity <= 0) {
                        $baseQuantity = $this->getBaseQuantity(
                            $item->productUnit,
                            (float) $item->quantity
                        );
                    }

                    $this->decreaseStockForDamage(
                        damage: $inventoryDamage,
                        item: $item,
                        baseQuantity: $baseQuantity
                    );
                }

                $this->createJournalEntryForDamage($inventoryDamage);

                $inventoryDamage->update([
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
            ->route('inventory-damages.show', $inventoryDamage)
            ->with('success', 'تم ترحيل سند التالف وخصم الكمية من المخزون.');
    }

    public function cancel(InventoryDamage $inventoryDamage)
    {
        $this->assertDamageAccess($inventoryDamage);

        if (! $inventoryDamage->isPosted()) {
            return back()->withErrors([
                'error' => 'لا يمكن إلغاء إلا سند تالف مرحل.',
            ]);
        }

        $inventoryDamage->load([
            'items.product',
            'items.productUnit.unit',
        ]);

        try {
            DB::transaction(function () use ($inventoryDamage) {
                $inventoryDamage = InventoryDamage::query()
                    ->with([
                        'items.product',
                        'items.productUnit.unit',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($inventoryDamage->id);

                    foreach ($inventoryDamage->items as $item) {
                        $baseQuantity = (float) ($item->base_quantity ?? 0);

                        if ($baseQuantity <= 0) {
                            $baseQuantity = $this->getBaseQuantity(
                                $item->productUnit,
                                (float) $item->quantity
                            );
                        }

                        $this->restoreStockAfterDamageCancel(
                            damage: $inventoryDamage,
                            item: $item,
                            baseQuantity: $baseQuantity
                        );
                    }
                
                $this->reverseJournalEntriesForDamage($inventoryDamage);

                $inventoryDamage->update([
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
            ->route('inventory-damages.show', $inventoryDamage)
            ->with('success', 'تم إلغاء سند التالف وإرجاع الكمية للمخزون.');
    }

    public function print(InventoryDamage $inventoryDamage)
    {
        $this->assertDamageAccess($inventoryDamage);

        $inventoryDamage->load([
            'warehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('inventory-damages.print', compact('inventoryDamage'));
    }

    public function productUnits(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'الصنف غير نشط.',
            ], 404);
        }

        $units = $product->units()
            ->with('unit')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $units->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'unit_name' => $productUnit->unit?->unit_name_ar
                        ?? $productUnit->unit?->unit_name
                        ?? $productUnit->unit?->name
                        ?? '-',
                    'factor' => (float) ($productUnit->factor ?? 1),
                    'purchase_price' => (float) ($productUnit->purchase_price ?? 0),
                    'is_default' => (int) ($productUnit->is_default ?? 0),
                ];
            })->values(),
        ]);
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

    private function decreaseStockForDamage(
        InventoryDamage $damage,
        InventoryDamageItem $item,
        float $baseQuantity
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $damage->warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new Exception('لا يوجد رصيد للصنف في المستودع.');
        }

        $balanceBefore = (float) $stock->quantity;
        $balanceAfter = round($balanceBefore - $baseQuantity, 3);

        if ($balanceAfter < 0) {
            throw new Exception('الرصيد لا يكفي لإتمام عملية الإتلاف.');
        }

        $unitCost = (float) ($stock->average_cost ?? 0);
        $totalCost = round($baseQuantity * $unitCost, 2);

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        $item->update([
            'base_quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('DMG'),
            'product_id' => $item->product_id,
            'warehouse_id' => $damage->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'damage',
            'quantity' => -1 * $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => -1 * $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => InventoryDamage::class,
            'reference_id' => $damage->id,
            'notes' => 'إتلاف مخزون رقم: ' . $damage->damage_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function restoreStockAfterDamageCancel(
        InventoryDamage $damage,
        InventoryDamageItem $item,
        float $baseQuantity
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $damage->warehouse_id)
            ->lockForUpdate()
            ->first();

        $unitCost = (float) ($item->unit_cost ?? 0);

        if (! $stock) {
            $stock = new ProductStock();
            $stock->product_id = $item->product_id;
            $stock->warehouse_id = $damage->warehouse_id;
            $stock->quantity = 0;
            $stock->average_cost = $unitCost;
            $stock->save();
        }

        $balanceBefore = (float) $stock->quantity;
        $oldAverageCost = (float) ($stock->average_cost ?? 0);

        $balanceAfter = round($balanceBefore + $baseQuantity, 3);

        $newAverageCost = $balanceAfter > 0
            ? round((($balanceBefore * $oldAverageCost) + ($baseQuantity * $unitCost)) / $balanceAfter, 4)
            : $unitCost;

        $stock->update([
            'quantity' => $balanceAfter,
            'average_cost' => $newAverageCost,
        ]);

        $totalCost = round($baseQuantity * $unitCost, 2);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('DMG-CAN'),
            'product_id' => $item->product_id,
            'warehouse_id' => $damage->warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'damage',
            'quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => InventoryDamage::class,
            'reference_id' => $damage->id,
            'notes' => 'إلغاء إتلاف مخزون رقم: ' . $damage->damage_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function createJournalEntryForDamage(InventoryDamage $damage): void
    {
        $damage->loadMissing([
            'warehouse',
            'items',
        ]);

        $totalCost = round((float) $damage->items->sum('total_cost'), 2);

        if ($totalCost <= 0) {
            return;
        }

        $damageExpenseAccount = $this->accountId('inventory_damage_expense_account');
        $inventoryAccount = $this->accountId('inventory_account');

        $branchId = $damage->warehouse?->branch_id;

        $this->journalEntryService->create([
            'entry_date' => $damage->damage_date,
            'document_type' => 'inventory_damage',
            'document_number' => $damage->damage_no,
            'reference_type' => InventoryDamage::class,
            'reference_id' => $damage->id,
            'description' => 'قيد إتلاف مخزون رقم ' . $damage->damage_no,
            'created_by' => auth()->id(),
            'lines' => [
                [
                    'account_id' => $damageExpenseAccount,
                    'debit' => $totalCost,
                    'credit' => 0,
                    'description' => 'خسائر تلف المخزون - سند رقم ' . $damage->damage_no,
                    'customer_id' => null,
                    'branch_id' => $branchId,
                    'cost_center_id' => null,
                ],
                [
                    'account_id' => $inventoryAccount,
                    'debit' => 0,
                    'credit' => $totalCost,
                    'description' => 'تخفيض المخزون بسبب التالف - سند رقم ' . $damage->damage_no,
                    'customer_id' => null,
                    'branch_id' => $branchId,
                    'cost_center_id' => null,
                ],
            ],
        ]);
    }

    private function reverseJournalEntriesForDamage(InventoryDamage $damage): void
    {
        $journalEntries = JournalEntry::query()
            ->where('reference_type', InventoryDamage::class)
            ->where('reference_id', $damage->id)
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
    private function generateDamageNo(): string
    {
        $prefix = 'DMG-' . now()->format('Ymd') . '-';

        $lastId = (int) InventoryDamage::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateTransactionNo(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductStock;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseTransferController extends Controller
{
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

    private function actorCanSeeAllCompanyBranches(): bool
    {
        return in_array($this->actorUserType(), [
            'master',
            'system_admin',
            'company_owner',
            'company_admin',
        ], true);
    }

    private function actorBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        /*
            master / system_admin:
            يرى كل الفروع.
        */
        if ($this->actorCanSeeAllCompanies()) {
            return null;
        }

        /*
            company_owner / company_admin:
            يرى كل فروع شركته.
        */
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

        /*
            branch_admin / user:
            يرى فرعه فقط.
        */
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
        return $this->applyWarehouseScope(
                Warehouse::query(),
                true
            )
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

    private function applyTransferScope($query)
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return $query;
        }

        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($branchIds) {
            $q->whereHas('fromWarehouse', function ($warehouseQuery) use ($branchIds) {
                $warehouseQuery->whereIn('branch_id', $branchIds);
            })
            ->orWhereHas('toWarehouse', function ($warehouseQuery) use ($branchIds) {
                $warehouseQuery->whereIn('branch_id', $branchIds);
            });
        });
    }

    private function assertTransferAccess(WarehouseTransfer $warehouseTransfer): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        if (empty($branchIds)) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا التحويل.');
        }

        $warehouseTransfer->loadMissing(['fromWarehouse', 'toWarehouse']);

        $fromBranchId = (int) ($warehouseTransfer->fromWarehouse?->branch_id ?? 0);
        $toBranchId = (int) ($warehouseTransfer->toWarehouse?->branch_id ?? 0);

        abort_unless(
            in_array($fromBranchId, $branchIds, true) || in_array($toBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية الوصول إلى تحويل من فرع آخر.'
        );
    }

    private function assertTransferManageAccess(WarehouseTransfer $warehouseTransfer): void
    {
        $branchIds = $this->actorBranchIds();

        if (is_null($branchIds)) {
            return;
        }

        if (empty($branchIds)) {
            abort(403, 'لا تملك صلاحية إدارة هذا التحويل.');
        }

        $warehouseTransfer->loadMissing(['fromWarehouse', 'toWarehouse']);

        $fromBranchId = (int) ($warehouseTransfer->fromWarehouse?->branch_id ?? 0);
        $toBranchId = (int) ($warehouseTransfer->toWarehouse?->branch_id ?? 0);

        abort_unless(
            in_array($fromBranchId, $branchIds, true) && in_array($toBranchId, $branchIds, true),
            403,
            'لا تملك صلاحية إدارة تحويل يحتوي على مستودع من فرع آخر.'
        );
    }

    public function index()
    {
        $query = WarehouseTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'creator']);

        $this->applyTransferScope($query);

        $transfers = $query
            ->latest()
            ->paginate(20);

        return view('warehouse-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = $this->activeWarehouses();

        return view('warehouse-transfers.create', compact('warehouses'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'transfer_date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ], [
            'to_warehouse_id.different' => 'لا يمكن التحويل من نفس المستودع إلى نفسه.',
            'items.required' => 'يرجى إضافة صنف واحد على الأقل.',
            'items.min' => 'يرجى إضافة صنف واحد على الأقل.',
        ]);

        $this->assertWarehouseAllowed((int) $data['from_warehouse_id']);
        $this->assertWarehouseAllowed((int) $data['to_warehouse_id']);

        $transfer = DB::transaction(function () use ($data) {
            $transfer = WarehouseTransfer::create([
                'transfer_no' => $this->generateTransferNo(),
                'transfer_date' => $data['transfer_date'],
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];

                $productUnit = ProductUnit::query()
                    ->whereKey($item['product_unit_id'])
                    ->where('product_id', $item['product_id'])
                    ->firstOrFail();

                $baseQuantity = $this->getBaseQuantity($productUnit, $quantity);

                $sourceStock = ProductStock::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['from_warehouse_id'])
                    ->first();

                $unitCost = (float) (
                    $sourceStock?->average_cost
                    ?? $item['unit_cost']
                    ?? 0
                );

                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($baseQuantity * $unitCost, 2),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer;
        });

        return redirect()
            ->route('warehouse-transfers.show', $transfer)
            ->with('success', 'تم إنشاء تحويل المستودعات كمسودة.');
    }

    public function show(WarehouseTransfer $warehouseTransfer)
    {
        $this->assertTransferAccess($warehouseTransfer);

        $warehouseTransfer->load([
            'fromWarehouse',
            'toWarehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('warehouse-transfers.show', compact('warehouseTransfer'));
    }

    public function post(WarehouseTransfer $warehouseTransfer)
    {
        $this->assertTransferManageAccess($warehouseTransfer);

        if (! $warehouseTransfer->isDraft()) {
            return back()->withErrors([
                'error' => 'لا يمكن ترحيل تحويل غير مسودة.',
            ]);
        }

        $warehouseTransfer->load([
            'items.product',
            'items.productUnit.unit',
        ]);

        if ($warehouseTransfer->items->isEmpty()) {
            return back()->withErrors([
                'error' => 'لا يمكن ترحيل تحويل بدون أصناف.',
            ]);
        }

        $errors = [];

        foreach ($warehouseTransfer->items as $item) {
            $baseQuantity = $this->getBaseQuantity(
                $item->productUnit,
                (float) $item->quantity
            );

            $stock = ProductStock::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseTransfer->from_warehouse_id)
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
                    . '، المطلوب: '
                    . number_format($baseQuantity, 3);
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors);
        }

        try {
            DB::transaction(function () use ($warehouseTransfer) {
                $warehouseTransfer = WarehouseTransfer::query()
                    ->with([
                        'items.product',
                        'items.productUnit.unit',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($warehouseTransfer->id);

                foreach ($warehouseTransfer->items as $item) {
                    $baseQuantity = $this->getBaseQuantity(
                        $item->productUnit,
                        (float) $item->quantity
                    );

                    $sourceStock = ProductStock::query()
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_id', $warehouseTransfer->from_warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $sourceStock) {
                        throw new Exception('لا يوجد رصيد للصنف في المستودع المصدر.');
                    }

                    $unitCost = (float) ($sourceStock->average_cost ?? 0);
                    $totalCost = round($baseQuantity * $unitCost, 2);

                    $item->update([
                        'base_quantity' => $baseQuantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                    ]);

                    $this->decreaseSourceWarehouse(
                        transfer: $warehouseTransfer,
                        item: $item,
                        baseQuantity: $baseQuantity,
                        unitCost: $unitCost,
                        totalCost: $totalCost
                    );

                    $this->increaseTargetWarehouse(
                        transfer: $warehouseTransfer,
                        item: $item,
                        baseQuantity: $baseQuantity,
                        unitCost: $unitCost,
                        totalCost: $totalCost
                    );
                }

                $warehouseTransfer->update([
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
            ->route('warehouse-transfers.show', $warehouseTransfer)
            ->with('success', 'تم ترحيل التحويل وتحديث حركة المخزون.');
    }

    public function cancel(WarehouseTransfer $warehouseTransfer)
    {
        $this->assertTransferManageAccess($warehouseTransfer);
        
        if (! $warehouseTransfer->isPosted()) {
            return back()->withErrors([
                'error' => 'لا يمكن إلغاء إلا التحويل المرحل.',
            ]);
        }

        $warehouseTransfer->load([
            'items.product',
            'items.productUnit.unit',
        ]);

        if ($warehouseTransfer->items->isEmpty()) {
            return back()->withErrors([
                'error' => 'لا يمكن إلغاء تحويل بدون أصناف.',
            ]);
        }

        $errors = [];

        foreach ($warehouseTransfer->items as $item) {
            $baseQuantity = (float) ($item->base_quantity ?? 0);

            if ($baseQuantity <= 0) {
                $baseQuantity = $this->getBaseQuantity(
                    $item->productUnit,
                    (float) $item->quantity
                );
            }

            $targetStock = ProductStock::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseTransfer->to_warehouse_id)
                ->first();

            $availableQuantity = (float) ($targetStock?->quantity ?? 0);

            if ($availableQuantity < $baseQuantity) {
                $productName = $item->product?->product_name_ar
                    ?? $item->product?->product_name_en
                    ?? ('صنف #' . $item->product_id);

                $errors[] = 'لا يمكن إلغاء التحويل للصنف: '
                    . $productName
                    . '. رصيد المستودع المستلم لا يكفي للعكس. المتاح: '
                    . number_format($availableQuantity, 3)
                    . '، المطلوب عكسه: '
                    . number_format($baseQuantity, 3);
            }
        }

        if (! empty($errors)) {
            return back()->withErrors($errors);
        }

        try {
            DB::transaction(function () use ($warehouseTransfer) {
                $warehouseTransfer = WarehouseTransfer::query()
                    ->with([
                        'items.product',
                        'items.productUnit.unit',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($warehouseTransfer->id);

                foreach ($warehouseTransfer->items as $item) {
                    $baseQuantity = (float) ($item->base_quantity ?? 0);

                    if ($baseQuantity <= 0) {
                        $baseQuantity = $this->getBaseQuantity(
                            $item->productUnit,
                            (float) $item->quantity
                        );
                    }

                    $unitCost = (float) ($item->unit_cost ?? 0);
                    $totalCost = round($baseQuantity * $unitCost, 2);

                    $this->decreaseTargetWarehouseAfterCancel(
                        transfer: $warehouseTransfer,
                        item: $item,
                        baseQuantity: $baseQuantity,
                        unitCost: $unitCost,
                        totalCost: $totalCost
                    );

                    $this->restoreSourceWarehouseAfterCancel(
                        transfer: $warehouseTransfer,
                        item: $item,
                        baseQuantity: $baseQuantity,
                        unitCost: $unitCost,
                        totalCost: $totalCost
                    );
                }

                $warehouseTransfer->update([
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
            ->route('warehouse-transfers.show', $warehouseTransfer)
            ->with('success', 'تم إلغاء التحويل وإنشاء حركات عكسية.');
    }

    public function print(WarehouseTransfer $warehouseTransfer)
    {
        $this->assertTransferAccess($warehouseTransfer);

        $warehouseTransfer->load([
            'fromWarehouse',
            'toWarehouse',
            'items.product',
            'items.productUnit.unit',
            'creator',
            'poster',
            'canceller',
        ]);

        return view('warehouse-transfers.print', compact('warehouseTransfer'));
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
                    'unit_name' => $productUnit->unit?->unit_name
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

    private function decreaseSourceWarehouse(
        WarehouseTransfer $transfer,
        WarehouseTransferItem $item,
        float $baseQuantity,
        float $unitCost,
        float $totalCost
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $transfer->from_warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new Exception('لا يوجد رصيد للصنف في المستودع المصدر.');
        }

        $balanceBefore = (float) $stock->quantity;
        $balanceAfter = round($balanceBefore - $baseQuantity, 3);

        if ($balanceAfter < 0) {
            throw new Exception('الرصيد لا يكفي لإتمام التحويل.');
        }

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('TR-OUT'),
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->from_warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'transfer_out',
            'quantity' => -1 * $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => -1 * $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => WarehouseTransfer::class,
            'reference_id' => $transfer->id,
            'notes' => 'خروج تحويل مستودعي رقم: ' . $transfer->transfer_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function increaseTargetWarehouse(
        WarehouseTransfer $transfer,
        WarehouseTransferItem $item,
        float $baseQuantity,
        float $unitCost,
        float $totalCost
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $transfer->to_warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = new ProductStock();
            $stock->product_id = $item->product_id;
            $stock->warehouse_id = $transfer->to_warehouse_id;
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

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('TR-IN'),
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'transfer_in',
            'quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => WarehouseTransfer::class,
            'reference_id' => $transfer->id,
            'notes' => 'دخول تحويل مستودعي رقم: ' . $transfer->transfer_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function decreaseTargetWarehouseAfterCancel(
        WarehouseTransfer $transfer,
        WarehouseTransferItem $item,
        float $baseQuantity,
        float $unitCost,
        float $totalCost
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $transfer->to_warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new Exception('لا يوجد رصيد للصنف في المستودع المستلم.');
        }

        $balanceBefore = (float) $stock->quantity;
        $balanceAfter = round($balanceBefore - $baseQuantity, 3);

        if ($balanceAfter < 0) {
            throw new Exception('رصيد المستودع المستلم لا يكفي لإلغاء التحويل.');
        }

        $stock->update([
            'quantity' => $balanceAfter,
        ]);

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('TR-CAN-OUT'),
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'transfer_out',
            'quantity' => -1 * $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => -1 * $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => WarehouseTransfer::class,
            'reference_id' => $transfer->id,
            'notes' => 'إلغاء دخول تحويل مستودعي رقم: ' . $transfer->transfer_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }

    private function restoreSourceWarehouseAfterCancel(
        WarehouseTransfer $transfer,
        WarehouseTransferItem $item,
        float $baseQuantity,
        float $unitCost,
        float $totalCost
    ): void {
        $stock = ProductStock::query()
            ->where('product_id', $item->product_id)
            ->where('warehouse_id', $transfer->from_warehouse_id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = new ProductStock();
            $stock->product_id = $item->product_id;
            $stock->warehouse_id = $transfer->from_warehouse_id;
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

        InventoryTransaction::create([
            'transaction_no' => $this->generateTransactionNo('TR-CAN-IN'),
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->from_warehouse_id,
            'product_unit_id' => $item->product_unit_id,
            'transaction_type' => 'transfer_in',
            'quantity' => $baseQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => WarehouseTransfer::class,
            'reference_id' => $transfer->id,
            'notes' => 'إلغاء خروج تحويل مستودعي رقم: ' . $transfer->transfer_no . ' - بند #' . $item->id,
            'created_by' => auth()->id(),
        ]);
    }


    private function generateTransferNo(): string
    {
        $prefix = 'WT-' . now()->format('Ymd') . '-';

        $lastId = (int) WarehouseTransfer::query()->max('id') + 1;

        return $prefix . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    private function generateTransactionNo(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

}
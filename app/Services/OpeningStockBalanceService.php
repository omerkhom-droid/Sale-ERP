<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\InventoryTransaction;
use App\Models\OpeningStockBalance;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OpeningStockBalanceService
{
    public function store(array $data): OpeningStockBalance
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | منع تكرار الرصيد الافتتاحي لنفس المنتج في نفس المستودع
            |--------------------------------------------------------------------------
            */
            $exists = OpeningStockBalance::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->exists();

            if ($exists) {
                throw new Exception('تم إدخال رصيد افتتاحي لهذا المنتج في هذا المستودع مسبقًا.');
            }

            $productUnit = ProductUnit::where('id', $data['product_unit_id'])
                ->where('product_id', $data['product_id'])
                ->firstOrFail();

            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            $branchId = null;

            if (Schema::hasColumn('warehouses', 'branch_id')) {
                $branchId = $warehouse->branch_id;
            }

            $quantity = (float) $data['quantity'];
            $factor = (float) $productUnit->factor;

            $baseQuantity = $quantity * $factor;

            $unitCost = (float) $data['unit_cost'];
            $totalCost = round($quantity * $unitCost, 2);

            if ($baseQuantity <= 0) {
                throw new Exception('كمية الرصيد الافتتاحي يجب أن تكون أكبر من صفر.');
            }

            if ($totalCost < 0) {
                throw new Exception('تكلفة الرصيد الافتتاحي غير صحيحة.');
            }

            $stock = ProductStock::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stockData = [
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ];

                if (Schema::hasColumn('product_stocks', 'average_cost')) {
                    $stockData['average_cost'] = 0;
                }

                $stock = ProductStock::create($stockData);
            }

            $balanceBefore = (float) $stock->quantity;
            $balanceAfter = $balanceBefore + $baseQuantity;

            $oldAverageCost = Schema::hasColumn('product_stocks', 'average_cost')
                ? (float) ($stock->average_cost ?? 0)
                : 0;

            $oldStockValue = $balanceBefore * $oldAverageCost;
            $newAverageCost = $balanceAfter > 0
                ? round(($oldStockValue + $totalCost) / $balanceAfter, 4)
                : 0;

            $openingBalance = OpeningStockBalance::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'product_unit_id' => $data['product_unit_id'],
                'quantity' => $quantity,
                'base_quantity' => $baseQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $stockUpdateData = [
                'quantity' => $balanceAfter,
            ];

            if (Schema::hasColumn('product_stocks', 'average_cost')) {
                $stockUpdateData['average_cost'] = $newAverageCost;
            }

            $stock->update($stockUpdateData);

            $transactionNo = $this->generateTransactionNo();

            InventoryTransaction::create([
                'transaction_no' => $transactionNo,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'product_unit_id' => $data['product_unit_id'],
                'transaction_type' => 'opening_balance',
                'quantity' => $baseQuantity,
                'unit_cost' => $baseQuantity > 0 ? round($totalCost / $baseQuantity, 4) : 0,
                'total_cost' => $totalCost,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => OpeningStockBalance::class,
                'reference_id' => $openingBalance->id,
                'notes' => $data['notes'] ?? 'رصيد افتتاحي للمخزون',
                'created_by' => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | القيد المحاسبي
            |--------------------------------------------------------------------------
            | من حـ / المخزون
            |     إلى حـ / أرصدة افتتاحية
            */
            if ($totalCost > 0) {
                $this->createJournalEntry(
                    openingBalance: $openingBalance,
                    transactionNo: $transactionNo,
                    amount: $totalCost,
                    branchId: $branchId
                );
            }

            return $openingBalance;
        });
    }


    private function createJournalEntry(
        OpeningStockBalance $openingBalance,
        string $transactionNo,
        float $amount,
        ?int $branchId = null
    ): void {
        $inventoryAccountId = $this->accountId('inventory_account');
        $openingBalanceEquityAccountId = $this->accountId('opening_balance_equity');

        app(JournalEntryService::class)->create([
            'entry_date' => now()->toDateString(),
            'document_type' => 'opening_stock_balance',
            'document_number' => $transactionNo,

            'reference_type' => OpeningStockBalance::class,
            'reference_id' => $openingBalance->id,

            'description' => 'قيد رصيد افتتاحي للمخزون رقم ' . $transactionNo,
            'created_by' => auth()->id(),

            'lines' => [
                [
                    'account_id' => $inventoryAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'إثبات رصيد افتتاحي للمخزون',
                    'branch_id' => $branchId,
                ],
                [
                    'account_id' => $openingBalanceEquityAccountId,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'مقابل الرصيد الافتتاحي للمخزون',
                    'branch_id' => $branchId,
                ],
            ],
        ]);
    }


    private function accountId(string $key): int
    {
        $query = AccountSetting::query();

        if (Schema::hasColumn('account_settings', 'setting_key')) {
            $query->where('setting_key', $key);
        } elseif (Schema::hasColumn('account_settings', 'account_key')) {
            $query->where('account_key', $key);
        } elseif (Schema::hasColumn('account_settings', 'key')) {
            $query->where('key', $key);
        } else {
            throw new Exception('لا يوجد عمود مفتاح في جدول account_settings.');
        }

        $setting = $query->first();

        if (!$setting || !$setting->account_id) {
            throw new Exception('يرجى ضبط الحساب المحاسبي: ' . $key);
        }

        return (int) $setting->account_id;
    }


    private function generateTransactionNo(): string
    {
        return 'OB-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}
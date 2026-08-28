<?php

namespace App\Services;

use App\Models\InventoryCount;
use Carbon\Carbon;
use Exception;

class InventoryGuardService
{
    public function assertDateAfterLastPostedInventoryCount(
        int $warehouseId,
        string $documentDate,
        string $documentName = 'المستند'
    ): void {
        $lastCountDate = InventoryCount::query()
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'posted')
            ->max('count_date');

        if (! $lastCountDate) {
            return;
        }

        $documentDateValue = Carbon::parse($documentDate)->toDateString();
        $lastCountDateValue = Carbon::parse($lastCountDate)->toDateString();

        if ($documentDateValue <= $lastCountDateValue) {
            throw new Exception(
                'لا يمكن ترحيل ' . $documentName .
                ' بتاريخ يسبق أو يساوي آخر جرد مرحل للمستودع. آخر جرد بتاريخ: ' .
                $lastCountDateValue
            );
        }
    }
}
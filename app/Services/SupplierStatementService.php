<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierStatementService
{
    public function getStatement(
        int $supplierId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $supplier = Supplier::findOrFail($supplierId);

        $payableAccountId = $this->accountId('accounts_payable');

        $journalLineTable = 'journal_entry_lines';
        $journalEntryTable = 'journal_entries';

        $journalEntryForeignKey = Schema::hasColumn($journalLineTable, 'journal_entry_id')
            ? 'journal_entry_id'
            : 'journal_id';

        $entryDateColumn = Schema::hasColumn($journalEntryTable, 'entry_date')
            ? 'entry_date'
            : 'date';

        $documentTypeColumn = Schema::hasColumn($journalEntryTable, 'document_type')
            ? 'document_type'
            : null;

        $documentNumberColumn = Schema::hasColumn($journalEntryTable, 'document_number')
            ? 'document_number'
            : null;

        $referenceTypeColumn = Schema::hasColumn($journalEntryTable, 'reference_type')
            ? 'reference_type'
            : null;

        $referenceIdColumn = Schema::hasColumn($journalEntryTable, 'reference_id')
            ? 'reference_id'
            : null;

        $descriptionColumn = Schema::hasColumn($journalEntryTable, 'description')
            ? 'description'
            : null;

        $lineDescriptionColumn = Schema::hasColumn($journalLineTable, 'description')
            ? 'description'
            : null;

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');

        $query = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->where('jel.supplier_id', $supplierId)
            ->where('jel.account_id', $payableAccountId)
            ->when($dateTo, function ($query) use ($dateTo, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '<=', $dateTo);
            })
            ->orderBy('je.' . $entryDateColumn)
            ->orderBy('je.id')
            ->orderBy('jel.id');

        if (Schema::hasColumn($journalEntryTable, 'status')) {
            $query->where(function ($q) {
                $q->whereNull('je.status')
                    ->orWhereNotIn('je.status', ['cancelled', 'void']);
            });
        }

        if ($branchId && $hasBranchColumn) {
            $query->where('jel.branch_id', $branchId);
        }

        if ($costCenterId && $hasCostCenterColumn) {
            $query->where('jel.cost_center_id', $costCenterId);
        }

        $lines = $query->select([
                'jel.id as line_id',
                'jel.debit',
                'jel.credit',
                'je.id as journal_entry_id',
                'je.' . $entryDateColumn . ' as entry_date',

                DB::raw($hasBranchColumn
                    ? 'jel.branch_id as branch_id'
                    : 'NULL as branch_id'
                ),

                DB::raw($hasCostCenterColumn
                    ? 'jel.cost_center_id as cost_center_id'
                    : 'NULL as cost_center_id'
                ),

                DB::raw($lineDescriptionColumn
                    ? 'jel.' . $lineDescriptionColumn . ' as line_description'
                    : 'NULL as line_description'
                ),

                DB::raw($documentTypeColumn
                    ? 'je.' . $documentTypeColumn . ' as document_type'
                    : 'NULL as document_type'
                ),

                DB::raw($documentNumberColumn
                    ? 'je.' . $documentNumberColumn . ' as document_no'
                    : 'NULL as document_no'
                ),

                DB::raw($referenceTypeColumn
                    ? 'je.' . $referenceTypeColumn . ' as reference_type'
                    : 'NULL as reference_type'
                ),

                DB::raw($referenceIdColumn
                    ? 'je.' . $referenceIdColumn . ' as reference_id'
                    : 'NULL as reference_id'
                ),

                DB::raw($descriptionColumn
                    ? 'je.' . $descriptionColumn . ' as entry_description'
                    : 'NULL as entry_description'
                ),
            ])
            ->get();

        $runningBalance = 0;
        $openingBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $movements = [];

        foreach ($lines as $line) {
            $date = substr((string) $line->entry_date, 0, 10);

            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);

            /*
                حساب الموردين طبيعته دائن:
                الدائن يزيد رصيد المورد
                المدين يقلل رصيد المورد
            */
            $runningBalance = round($runningBalance + $credit - $debit, 2);

            if ($dateFrom && $date < $dateFrom) {
                $openingBalance = $runningBalance;
                continue;
            }

            $type = $this->normalizeType(
                $line->document_type,
                $line->reference_type
            );

            $description = $line->line_description
                ?: $line->entry_description
                ?: 'حركة على حساب المورد';

            $movements[] = [
                'date' => $date,

                'type' => $type,
                'document_type' => $type,
                'document_type_label' => $this->typeLabel($type),

                'document_no' => $line->document_no ?: ('JE-' . $line->journal_entry_id),
                'description' => $description,

                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,

                'balance_type' => $runningBalance > 0
                    ? 'credit'
                    : ($runningBalance < 0 ? 'debit' : 'zero'),

                'branch_id' => $line->branch_id ?? null,
                'cost_center_id' => $line->cost_center_id ?? null,

                'reference_type' => $line->reference_type,
                'reference_id' => $line->reference_id,
                'journal_entry_id' => $line->journal_entry_id,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $movementsCollection = collect($movements);

        return [
            'supplier' => $supplier,

            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'opening_balance' => round($openingBalance, 2),

            'movements' => $movementsCollection,
            'transactions' => $movementsCollection,

            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),

            'balance' => round($runningBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'current_balance' => round($runningBalance, 2),
        ];
    }


    public function build(
        int $supplierId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        return $this->getStatement($supplierId, $fromDate, $toDate, $branchId, $costCenterId);
    }


    public function statement(
        int $supplierId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        return $this->getStatement($supplierId, $dateFrom, $dateTo, $branchId, $costCenterId);
    }


    public function generate(
        int $supplierId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        return $this->getStatement($supplierId, $dateFrom, $dateTo, $branchId, $costCenterId);
    }


    private function normalizeType(?string $documentType, ?string $referenceType): string
    {
        if (!empty($documentType)) {
            return $documentType;
        }

        return match ($referenceType) {
            'App\\Models\\PurchaseInvoice' => 'purchase_invoice',
            'App\\Models\\SupplierPaymentVoucher' => 'supplier_payment_voucher',
            'App\\Models\\PurchaseReturn' => 'purchase_return',

            'App\\Models\\OpeningBalance' => 'opening_balance',
            'App\\Models\\ManualJournalEntry' => 'manual_journal_entry',

            'App\\Models\\GeneralReceiptVoucher' => 'general_receipt_voucher',
            'App\\Models\\GeneralPaymentVoucher' => 'general_payment_voucher',

            default => 'journal_entry',
        };
    }


    private function typeLabel(string $type): string
    {
        return match ($type) {
            'purchase_invoice' => 'فاتورة شراء',
            'purchase_invoice_payment' => 'دفع مباشر لفاتورة شراء',
            'supplier_payment_voucher' => 'سند صرف مورد',
            'purchase_return' => 'مردود مشتريات',

            'opening_balance' => 'رصيد افتتاحي',
            'manual_journal_entry' => 'قيد يومية يدوي',

            'general_receipt_voucher' => 'سند قبض عام',
            'general_payment_voucher' => 'سند صرف عام',

            'journal_entry' => 'قيد يومية',
            default => $type,
        };
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
}
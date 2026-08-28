<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountLedgerReportService
{
    public function getReport(
        int $accountId,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $dateTo = $dateTo ?: now()->toDateString();

        $account = Account::findOrFail($accountId);

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

        $entryDescriptionColumn = Schema::hasColumn($journalEntryTable, 'description')
            ? 'description'
            : null;

        $lineDescriptionColumn = Schema::hasColumn($journalLineTable, 'description')
            ? 'description'
            : null;

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');


        /*
        |--------------------------------------------------------------------------
        | الرصيد الافتتاحي قبل تاريخ البداية
        |--------------------------------------------------------------------------
        */
        $openingBalance = 0;

        if ($dateFrom) {
            $openingQuery = DB::table($journalLineTable . ' as jel')
                ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
                ->where('jel.account_id', $accountId)
                ->whereDate('je.' . $entryDateColumn, '<', $dateFrom);

            if (Schema::hasColumn($journalEntryTable, 'status')) {
                $openingQuery->where(function ($q) {
                    $q->whereNull('je.status')
                        ->orWhereNotIn('je.status', ['cancelled', 'void']);
                });
            }

            if ($branchId && $hasBranchColumn) {
                $openingQuery->where('jel.branch_id', $branchId);
            }

            if ($costCenterId && $hasCostCenterColumn) {
                $openingQuery->where('jel.cost_center_id', $costCenterId);
            }

            $openingBalance = round((float) $openingQuery
                ->selectRaw('COALESCE(SUM(jel.debit - jel.credit), 0) as balance')
                ->value('balance'), 2);
        }


        /*
        |--------------------------------------------------------------------------
        | الحركات
        |--------------------------------------------------------------------------
        */
        $query = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->where('jel.account_id', $accountId)
            ->when($dateFrom, function ($query) use ($dateFrom, $entryDateColumn) {
                $query->whereDate('je.' . $entryDateColumn, '>=', $dateFrom);
            })
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

                DB::raw($entryDescriptionColumn
                    ? 'je.' . $entryDescriptionColumn . ' as entry_description'
                    : 'NULL as entry_description'
                ),
            ])
            ->get();

        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;
        $movements = [];

        foreach ($lines as $line) {
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);

            $runningBalance = round($runningBalance + $debit - $credit, 2);

            $totalDebit += $debit;
            $totalCredit += $credit;

            $type = $this->normalizeType(
                $line->document_type,
                $line->reference_type
            );

            $movements[] = [
                'date' => substr((string) $line->entry_date, 0, 10),

                'type' => $type,
                'document_type' => $type,
                'document_type_label' => $this->typeLabel($type),

                'document_no' => $line->document_no ?: ('JE-' . $line->journal_entry_id),

                'description' => $line->line_description
                    ?: $line->entry_description
                    ?: 'حركة على الحساب',

                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,

                'balance_type' => $runningBalance > 0
                    ? 'debit'
                    : ($runningBalance < 0 ? 'credit' : 'zero'),

                'branch_id' => $line->branch_id ?? null,
                'cost_center_id' => $line->cost_center_id ?? null,

                'reference_type' => $line->reference_type,
                'reference_id' => $line->reference_id,
                'journal_entry_id' => $line->journal_entry_id,
            ];
        }

        $closingBalance = round($runningBalance, 2);

        return [
            'account' => $account,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,

            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'opening_balance' => round($openingBalance, 2),

            'movements' => collect($movements),

            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),

            'closing_balance' => $closingBalance,
            'balance' => $closingBalance,
        ];
    }


    private function normalizeType(?string $documentType, ?string $referenceType): string
    {
        if (!empty($documentType)) {
            return $documentType;
        }

        return match ($referenceType) {
            'App\\Models\\SalesInvoice' => 'sales_invoice',
            'App\\Models\\CustomerReceiptVoucher' => 'customer_receipt_voucher',
            'App\\Models\\SalesReturn' => 'sales_return',
            'App\\Models\\CustomerRefundVoucher' => 'customer_refund_voucher',

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
            'sales_invoice' => 'فاتورة بيع',
            'customer_receipt_voucher' => 'سند قبض عميل',
            'sales_return' => 'مردود مبيعات',
            'customer_refund_voucher' => 'سند رد مبلغ للعميل',

            'purchase_invoice' => 'فاتورة شراء',
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
}
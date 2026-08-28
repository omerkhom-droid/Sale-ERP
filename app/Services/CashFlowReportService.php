<?php

namespace App\Services;

use App\Models\AccountSetting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashFlowReportService
{
    public function getReport(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $dateFrom = $dateFrom ?: now()->startOfYear()->toDateString();
        $dateTo = $dateTo ?: now()->toDateString();

        $cashBankAccountIds = $this->cashBankAccountIds();

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

        $hasBranchColumn = Schema::hasColumn($journalLineTable, 'branch_id');
        $hasCostCenterColumn = Schema::hasColumn($journalLineTable, 'cost_center_id');

        /*
        |--------------------------------------------------------------------------
        | رصيد النقدية والبنك بداية الفترة
        |--------------------------------------------------------------------------
        */
        $openingQuery = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->whereIn('jel.account_id', $cashBankAccountIds)
            ->where(function ($query) use ($entryDateColumn, $dateFrom, $documentTypeColumn, $referenceTypeColumn) {
                $query->whereDate('je.' . $entryDateColumn, '<', $dateFrom);

                /*
                    إذا كان قيد رصيد افتتاحي بتاريخ بداية الفترة، نحسبه ضمن الرصيد الافتتاحي
                    وليس كتدفق نقدي.
                */
                $query->orWhere(function ($q) use ($entryDateColumn, $dateFrom, $documentTypeColumn, $referenceTypeColumn) {
                    $q->whereDate('je.' . $entryDateColumn, '<=', $dateFrom);

                    $q->where(function ($qq) use ($documentTypeColumn, $referenceTypeColumn) {
                        if ($documentTypeColumn) {
                            $qq->where('je.' . $documentTypeColumn, 'opening_balance');
                        }

                        if ($referenceTypeColumn) {
                            $method = $documentTypeColumn ? 'orWhere' : 'where';
                            $qq->{$method}('je.' . $referenceTypeColumn, 'App\\Models\\OpeningBalance');
                        }
                    });
                });
            });

        $this->applyStatusFilter($openingQuery, $journalEntryTable);
        $this->applyBranchAndCostCenterFilter($openingQuery, $hasBranchColumn, $hasCostCenterColumn, $branchId, $costCenterId);

        $openingBalance = round((float) $openingQuery
            ->selectRaw('COALESCE(SUM(jel.debit - jel.credit), 0) as balance')
            ->value('balance'), 2);


        /*
        |--------------------------------------------------------------------------
        | حركات النقدية والبنك خلال الفترة
        |--------------------------------------------------------------------------
        */
        $cashLinesQuery = DB::table($journalLineTable . ' as jel')
            ->join($journalEntryTable . ' as je', 'jel.' . $journalEntryForeignKey, '=', 'je.id')
            ->whereIn('jel.account_id', $cashBankAccountIds)
            ->whereDate('je.' . $entryDateColumn, '>=', $dateFrom)
            ->whereDate('je.' . $entryDateColumn, '<=', $dateTo)
            ->orderBy('je.' . $entryDateColumn)
            ->orderBy('je.id')
            ->orderBy('jel.id');

        $this->applyStatusFilter($cashLinesQuery, $journalEntryTable);
        $this->applyBranchAndCostCenterFilter($cashLinesQuery, $hasBranchColumn, $hasCostCenterColumn, $branchId, $costCenterId);

        $cashLines = $cashLinesQuery
            ->select([
                'jel.id as line_id',
                'jel.account_id',
                'jel.debit',
                'jel.credit',
                'je.id as journal_entry_id',
                'je.' . $entryDateColumn . ' as entry_date',

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
            ->get()
            ->filter(function ($line) {
                return !$this->isOpeningBalanceMovement($line->document_type, $line->reference_type);
            });

        $journalEntryIds = $cashLines
            ->pluck('journal_entry_id')
            ->filter()
            ->unique()
            ->values();

        $counterLines = collect();

        if ($journalEntryIds->isNotEmpty()) {
            $accountTypeColumn = Schema::hasColumn('accounts', 'account_type')
                ? 'account_type'
                : (Schema::hasColumn('accounts', 'type') ? 'type' : null);

            $accountCodeColumn = Schema::hasColumn('accounts', 'account_code')
                ? 'account_code'
                : (Schema::hasColumn('accounts', 'code') ? 'code' : null);

            $accountNameColumn = Schema::hasColumn('accounts', 'account_name_ar')
                ? 'account_name_ar'
                : (Schema::hasColumn('accounts', 'account_name')
                    ? 'account_name'
                    : (Schema::hasColumn('accounts', 'name') ? 'name' : null));

            $counterLines = DB::table($journalLineTable . ' as jel')
                ->join('accounts as a', 'jel.account_id', '=', 'a.id')
                ->whereIn('jel.' . $journalEntryForeignKey, $journalEntryIds)
                ->whereNotIn('jel.account_id', $cashBankAccountIds)
                ->select([
                    'jel.' . $journalEntryForeignKey . ' as journal_entry_id',
                    'jel.account_id',
                    'jel.debit',
                    'jel.credit',

                    DB::raw($accountTypeColumn
                        ? 'a.' . $accountTypeColumn . ' as account_type'
                        : 'NULL as account_type'
                    ),

                    DB::raw($accountCodeColumn
                        ? 'a.' . $accountCodeColumn . ' as account_code'
                        : 'NULL as account_code'
                    ),

                    DB::raw($accountNameColumn
                        ? 'a.' . $accountNameColumn . ' as account_name'
                        : 'NULL as account_name'
                    ),
                ])
                ->get()
                ->groupBy('journal_entry_id');
        }

        $movements = [];

        foreach ($cashLines->groupBy('journal_entry_id') as $journalEntryId => $lines) {
            $firstLine = $lines->first();

            $cashDebit = round((float) $lines->sum('debit'), 2);
            $cashCredit = round((float) $lines->sum('credit'), 2);

            $netCashFlow = round($cashDebit - $cashCredit, 2);

            /*
                تحويل بين نقدية وبنك لن يؤثر على صافي النقدية، لذلك لا يظهر كتدفق.
            */
            if ($netCashFlow == 0) {
                continue;
            }

            $counterpartLines = $counterLines->get($journalEntryId, collect());

            $category = $this->classifyMovement(
                documentType: $firstLine->document_type,
                referenceType: $firstLine->reference_type,
                counterLines: $counterpartLines
            );

            $type = $this->normalizeType($firstLine->document_type, $firstLine->reference_type);

            $movements[] = [
                'date' => substr((string) $firstLine->entry_date, 0, 10),

                'category' => $category,
                'category_label' => $this->categoryLabel($category),

                'type' => $type,
                'document_type' => $type,
                'document_type_label' => $this->typeLabel($type),

                'document_no' => $firstLine->document_no ?: ('JE-' . $journalEntryId),
                'description' => $firstLine->entry_description ?: 'حركة نقدية',

                'cash_in' => $netCashFlow > 0 ? abs($netCashFlow) : 0,
                'cash_out' => $netCashFlow < 0 ? abs($netCashFlow) : 0,
                'net_cash_flow' => $netCashFlow,

                'reference_type' => $firstLine->reference_type,
                'reference_id' => $firstLine->reference_id,
                'journal_entry_id' => $journalEntryId,
            ];
        }

        $movements = collect($movements)
            ->sortBy([
                ['date', 'asc'],
                ['journal_entry_id', 'asc'],
            ])
            ->values();

        $operating = $movements->where('category', 'operating')->values();
        $investing = $movements->where('category', 'investing')->values();
        $financing = $movements->where('category', 'financing')->values();

        $operatingNet = round($operating->sum('net_cash_flow'), 2);
        $investingNet = round($investing->sum('net_cash_flow'), 2);
        $financingNet = round($financing->sum('net_cash_flow'), 2);

        $netCashFlow = round($operatingNet + $investingNet + $financingNet, 2);
        $closingBalance = round($openingBalance + $netCashFlow, 2);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,

            'cash_bank_account_ids' => $cashBankAccountIds,

            'opening_balance' => $openingBalance,

            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'movements' => $movements,

            'operating_in' => round($operating->sum('cash_in'), 2),
            'operating_out' => round($operating->sum('cash_out'), 2),
            'operating_net' => $operatingNet,

            'investing_in' => round($investing->sum('cash_in'), 2),
            'investing_out' => round($investing->sum('cash_out'), 2),
            'investing_net' => $investingNet,

            'financing_in' => round($financing->sum('cash_in'), 2),
            'financing_out' => round($financing->sum('cash_out'), 2),
            'financing_net' => $financingNet,

            'total_cash_in' => round($movements->sum('cash_in'), 2),
            'total_cash_out' => round($movements->sum('cash_out'), 2),
            'net_cash_flow' => $netCashFlow,
            'closing_balance' => $closingBalance,
        ];
    }


    private function applyStatusFilter($query, string $journalEntryTable): void
    {
        if (Schema::hasColumn($journalEntryTable, 'status')) {
            $query->where(function ($q) {
                $q->whereNull('je.status')
                    ->orWhereNotIn('je.status', ['cancelled', 'void']);
            });
        }
    }


    private function applyBranchAndCostCenterFilter(
        $query,
        bool $hasBranchColumn,
        bool $hasCostCenterColumn,
        ?int $branchId,
        ?int $costCenterId
    ): void {
        if ($branchId && $hasBranchColumn) {
            $query->where('jel.branch_id', $branchId);
        }

        if ($costCenterId && $hasCostCenterColumn) {
            $query->where('jel.cost_center_id', $costCenterId);
        }
    }


    private function cashBankAccountIds(): array
    {
        $ids = [];

        foreach (['cash_account', 'bank_account'] as $key) {
            $id = $this->accountIdOrNull($key);

            if ($id) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            throw new Exception('يرجى ضبط حساب النقدية أو البنك في إعدادات الحسابات.');
        }

        return $ids;
    }


    private function accountIdOrNull(string $key): ?int
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

        return $setting && $setting->account_id
            ? (int) $setting->account_id
            : null;
    }


    private function isOpeningBalanceMovement(?string $documentType, ?string $referenceType): bool
    {
        return $documentType === 'opening_balance'
            || $referenceType === 'App\\Models\\OpeningBalance';
    }


    private function classifyMovement(?string $documentType, ?string $referenceType, $counterLines): string
    {
        $type = $this->normalizeType($documentType, $referenceType);

        /*
            أغلب مستندات التشغيل في هذا النظام:
            مبيعات، مشتريات، قبض عملاء، صرف موردين، مردودات، سندات عامة.
        */
        if (in_array($type, [
            'sales_invoice',
            'customer_receipt_voucher',
            'sales_return',
            'customer_refund_voucher',
            'purchase_invoice',
            'supplier_payment_voucher',
            'purchase_return',
            'general_receipt_voucher',
            'general_payment_voucher',
            'manual_journal_entry',
            'journal_entry',
        ], true)) {
            /*
                إذا كان الحساب المقابل واضح أنه أصل ثابت، نعتبرها استثمارية.
                وإذا كان واضح أنه قرض/رأس مال/حقوق ملكية، نعتبرها تمويلية.
                غير ذلك تشغيلية.
            */
            foreach ($counterLines as $line) {
                $name = mb_strtolower((string) ($line->account_name ?? ''));
                $code = mb_strtolower((string) ($line->account_code ?? ''));
                $accountType = $line->account_type ?? null;

                if (in_array($accountType, ['equity'], true)) {
                    return 'financing';
                }

                if (in_array($accountType, ['liability', 'liabilities'], true)
                    && $this->looksLikeFinancingAccount($name, $code)) {
                    return 'financing';
                }

                if (in_array($accountType, ['asset', 'assets'], true)
                    && $this->looksLikeInvestingAccount($name, $code)) {
                    return 'investing';
                }
            }

            return 'operating';
        }

        return 'operating';
    }


    private function looksLikeInvestingAccount(string $name, string $code): bool
    {
        $text = $name . ' ' . $code;

        foreach ([
            'fixed',
            'asset',
            'property',
            'equipment',
            'vehicle',
            'furniture',
            'machine',
            'land',
            'building',
            'أصل ثابت',
            'اصول ثابتة',
            'أصول ثابتة',
            'معدات',
            'سيارات',
            'اثاث',
            'أثاث',
            'مباني',
            'أراضي',
            'اراضي',
            'آلات',
            'الات',
        ] as $word) {
            if (str_contains($text, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }


    private function looksLikeFinancingAccount(string $name, string $code): bool
    {
        $text = $name . ' ' . $code;

        foreach ([
            'loan',
            'capital',
            'equity',
            'owner',
            'shareholder',
            'قرض',
            'قروض',
            'رأس المال',
            'راس المال',
            'حقوق الملكية',
            'مالك',
            'الشركاء',
            'المساهمين',
        ] as $word) {
            if (str_contains($text, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
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

            'App\\Models\\GeneralReceiptVoucher' => 'general_receipt_voucher',
            'App\\Models\\GeneralPaymentVoucher' => 'general_payment_voucher',

            'App\\Models\\ManualJournalEntry' => 'manual_journal_entry',
            'App\\Models\\OpeningBalance' => 'opening_balance',

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

            'general_receipt_voucher' => 'سند قبض عام',
            'general_payment_voucher' => 'سند صرف عام',

            'manual_journal_entry' => 'قيد يومية يدوي',
            'opening_balance' => 'رصيد افتتاحي',
            'journal_entry' => 'قيد يومية',

            default => $type,
        };
    }


    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'operating' => 'أنشطة تشغيلية',
            'investing' => 'أنشطة استثمارية',
            'financing' => 'أنشطة تمويلية',
            default => $category,
        };
    }
}
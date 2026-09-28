<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\CustomerReceiptAllocation;
use App\Models\CustomerReceiptVoucher;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\CostCenter;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerReceiptVoucherService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | حفظ سند قبض عميل كمسودة أو حفظ وترحيل.
    */
    public function store(array $data): CustomerReceiptVoucher
    {
        return DB::transaction(function () use ($data) {

            $amount = round((float) $data['amount'], 2);

            $allocations = collect($data['allocations'] ?? [])
                ->filter(function ($row) {
                    return !empty($row['sales_invoice_id'])
                        && (float) ($row['amount'] ?? 0) > 0;
                })
                ->values();

            $allocatedAmount = round((float) $allocations->sum('amount'), 2);

            if ($allocatedAmount > $amount) {
                throw new Exception('إجمالي التوزيع أكبر من مبلغ سند القبض.');
            }

            $voucher = CustomerReceiptVoucher::create([
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNo(),

                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,

                'receipt_date' => $data['receipt_date'],
                'payment_method' => $data['payment_method'],

                'amount' => $amount,
                'allocated_amount' => $allocatedAmount,
                'unallocated_amount' => round($amount - $allocatedAmount, 2),

                'status' => 'draft',

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($allocations as $allocation) {
                CustomerReceiptAllocation::create([
                    'customer_receipt_voucher_id' => $voucher->id,
                    'sales_invoice_id' => $allocation['sales_invoice_id'],
                    'amount' => round((float) $allocation['amount'], 2),
                ]);
            }

            if (($data['save_action'] ?? 'draft') === 'post') {
                $this->post($voucher);
            }

            return $voucher->fresh([
                'customer',
                'branch',
                'allocations.salesInvoice',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | post
    |--------------------------------------------------------------------------
    | ترحيل سند القبض:
    | - تحديث الفواتير المسدد عليها
    | - إنشاء قيد محاسبي
    */
    public function post(CustomerReceiptVoucher $voucher): CustomerReceiptVoucher
    {
        return DB::transaction(function () use ($voucher) {

            $voucher = CustomerReceiptVoucher::with([
                    'allocations.salesInvoice',
                    'customer',
                ])
                ->lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'posted') {
                throw new Exception('سند القبض مرحل مسبقاً.');
            }

            if ($voucher->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل سند قبض ملغى.');
            }

            foreach ($voucher->allocations as $allocation) {

                $invoice = SalesInvoice::lockForUpdate()
                    ->findOrFail($allocation->sales_invoice_id);

                if ($invoice->status !== 'posted') {
                    throw new Exception('لا يمكن السداد على فاتورة غير مرحلة.');
                }

                if ((int) $invoice->customer_id !== (int) $voucher->customer_id) {
                    throw new Exception('يوجد توزيع على فاتورة لا تخص العميل المحدد.');
                }

                $amount = round((float) $allocation->amount, 2);

                app(SalesDocumentBalanceService::class)->refresh($invoice);

                if ($amount > (float) $invoice->remaining_amount) {
                    throw new Exception(
                        'مبلغ السداد أكبر من المتبقي على الفاتورة رقم: ' . $invoice->invoice_no
                    );
                }

                $newPaid = round((float) $invoice->paid_amount + $amount, 2);
                $netAmount = app(SalesDocumentBalanceService::class)->netAmount($invoice);
                $newRemaining = round($netAmount - $newPaid, 2);

                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'payment_status' => $this->paymentStatus($newPaid, $netAmount),
                ]);
            }

            $this->createJournalEntry($voucher);

            $voucher->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            app(\App\Services\AuditLogService::class)->log(
                action: 'post',
                module: 'Customer Receipt Voucher',
                description: 'تم ترحيل سند قبض عميل رقم ' . $voucher->voucher_no,
                model: $voucher,
                newValues: [
                    'voucher_no' => $voucher->voucher_no,
                    'customer_id' => $voucher->customer_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'receipt_date' => $voucher->receipt_date,
                    'payment_method' => $voucher->payment_method,
                    'amount' => $voucher->amount,
                    'allocated_amount' => $voucher->allocated_amount,
                    'unallocated_amount' => $voucher->unallocated_amount,
                    'status' => $voucher->status,
                ],
                branchId: $voucher->branch_id
            );

            return $voucher->fresh([
                'customer',
                'branch',
                'allocations.salesInvoice',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | cancel
    |--------------------------------------------------------------------------
    | إلغاء سند القبض:
    | - عكس تأثير السداد على الفواتير
    | - عكس القيد المحاسبي
    */
    public function cancel(CustomerReceiptVoucher $voucher, ?string $reason = null): CustomerReceiptVoucher
    {
        return DB::transaction(function () use ($voucher, $reason) {

            $voucher = CustomerReceiptVoucher::with([
                    'allocations.salesInvoice',
                ])
                ->lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'cancelled') {
                throw new Exception('سند القبض ملغى مسبقاً.');
            }

            if ($voucher->status === 'draft') {
                $voucher->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                app(\App\Services\AuditLogService::class)->log(
                    action: 'cancel',
                    module: 'Customer Receipt Voucher',
                    description: 'تم إلغاء سند قبض عميل مسودة رقم ' . $voucher->voucher_no,
                    model: $voucher,
                    oldValues: [
                        'status' => 'draft',
                        'voucher_no' => $voucher->voucher_no,
                        'customer_id' => $voucher->customer_id,
                        'amount' => $voucher->amount,
                        'allocated_amount' => $voucher->allocated_amount,
                    ],
                    newValues: [
                        'status' => 'cancelled',
                        'cancel_reason' => $reason,
                        'cancelled_at' => now(),
                    ],
                    branchId: $voucher->branch_id
                );

                return $voucher->fresh();
            }

            foreach ($voucher->allocations as $allocation) {

                $invoice = SalesInvoice::lockForUpdate()
                    ->findOrFail($allocation->sales_invoice_id);

                $amount = round((float) $allocation->amount, 2);

                $newPaid = round((float) $invoice->paid_amount - $amount, 2);

                if ($newPaid < 0) {
                    $newPaid = 0;
                }

                $netAmount = app(SalesDocumentBalanceService::class)->netAmount($invoice);
                $newRemaining = round($netAmount - $newPaid, 2);

                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'payment_status' => $this->paymentStatus($newPaid, $netAmount),
                ]);
            }

            /*
                عكس القيود المحاسبية الخاصة بسند القبض.
                JournalEntryService::reverse() عندك يستقبل JournalEntry Model.
            */
            $journalEntries = JournalEntry::where('reference_type', CustomerReceiptVoucher::class)
                ->where('reference_id', $voucher->id)
                ->get();

            foreach ($journalEntries as $entry) {
                $this->journalEntryService->reverse($entry);
            }

            $voucher->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            app(\App\Services\AuditLogService::class)->log(
                action: 'cancel',
                module: 'Customer Receipt Voucher',
                description: 'تم إلغاء سند قبض عميل مرحل رقم ' . $voucher->voucher_no,
                model: $voucher,
                oldValues: [
                    'status' => 'posted',
                    'voucher_no' => $voucher->voucher_no,
                    'customer_id' => $voucher->customer_id,
                    'amount' => $voucher->amount,
                    'allocated_amount' => $voucher->allocated_amount,
                ],
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                    'cancelled_at' => now(),
                ],
                branchId: $voucher->branch_id
            );

            return $voucher->fresh([
                'customer',
                'branch',
                'allocations.salesInvoice',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | createJournalEntry
    |--------------------------------------------------------------------------
    | قيد سند القبض:
    |
    | من حـ / الصندوق أو البنك
    |     إلى حـ / العملاء
    */
    private function createJournalEntry(CustomerReceiptVoucher $voucher): void
    {
        $cashOrBankAccount = $this->cashOrBankAccount($voucher->payment_method);
        $receivableAccount = $this->accountId('accounts_receivable');

        $this->journalEntryService->create([
            'entry_date' => $voucher->receipt_date,
            'document_type' => 'customer_receipt_voucher',
            'document_number' => $voucher->voucher_no,

            'reference_type' => CustomerReceiptVoucher::class,
            'reference_id' => $voucher->id,

            'description' => 'سند قبض عميل رقم ' . $voucher->voucher_no,

            'lines' => [
                [
                    'account_id' => $cashOrBankAccount,
                    'debit' => $voucher->amount,
                    'credit' => 0,
                    'description' => 'قبض من العميل',
                    'customer_id' => $voucher->customer_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
                [
                    'account_id' => $receivableAccount,
                    'debit' => 0,
                    'credit' => $voucher->amount,
                    'description' => 'تخفيض مديونية العميل',
                    'customer_id' => $voucher->customer_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
            ],
        ]);
    }


    private function cashOrBankAccount(string $paymentMethod): int
    {
        if (in_array($paymentMethod, ['bank_transfer', 'card'])) {
            return $this->accountId('bank_account');
        }

        return $this->accountId('cash_account');
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


    private function paymentStatus(float $paidAmount, float $totalAmount): string
    {
        if ($totalAmount <= 0) { return 'paid'; }

        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }


    private function generateVoucherNo(): string
    {
        return 'CRV-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}
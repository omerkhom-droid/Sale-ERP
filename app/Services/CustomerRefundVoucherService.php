<?php

namespace App\Services;

use App\Models\AccountSetting;
use App\Models\CustomerRefundVoucher;
use App\Models\JournalEntry;
use App\Models\SalesReturn;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerRefundVoucherService
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    | حفظ سند صرف العميل كمسودة أو حفظ وترحيل.
    */
    public function store(array $data): CustomerRefundVoucher
    {
        return DB::transaction(function () use ($data) {

            $salesReturn = SalesReturn::with([
                    'customer',
                    'branch',
                ])
                ->lockForUpdate()
                ->findOrFail($data['sales_return_id']);

            if ($salesReturn->status !== 'posted') {
                throw new Exception('لا يمكن صرف مبلغ للعميل إلا على مردود مبيعات مرحل.');
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);

            if ($amount <= 0) {
                throw new Exception('مبلغ سند الصرف يجب أن يكون أكبر من صفر.');
            }

            $availableAmount = $this->availableRefundAmount($salesReturn);

            if ($amount > $availableAmount) {
                throw new Exception(
                    'مبلغ الصرف أكبر من المبلغ المتاح. المتاح: ' . number_format($availableAmount, 2)
                );
            }

            $voucher = CustomerRefundVoucher::create([
                'voucher_no' => $data['voucher_no'] ?? $this->generateVoucherNo(),

                'sales_return_id' => $salesReturn->id,
                'customer_id' => $salesReturn->customer_id,
                'branch_id' => $salesReturn->branch_id,
                'cost_center_id' => $salesReturn->cost_center_id,
                'refund_date' => $data['refund_date'],
                'payment_method' => $data['payment_method'],

                'amount' => $amount,

                'status' => 'draft',

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if (($data['save_action'] ?? 'draft') === 'post') {
                $this->post($voucher);
            }

            return $voucher->fresh([
                'salesReturn',
                'customer',
                'branch',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | post
    |--------------------------------------------------------------------------
    | ترحيل سند صرف العميل:
    | - تحديث refunded_amount في مردود المبيعات
    | - إنشاء قيد محاسبي
    |
    | القيد:
    | من حـ / العملاء
    |     إلى حـ / الصندوق أو البنك
    */
    public function post(CustomerRefundVoucher $voucher): CustomerRefundVoucher
    {
        return DB::transaction(function () use ($voucher) {

            $voucher = CustomerRefundVoucher::with([
                    'salesReturn',
                    'customer',
                    'branch',
                ])
                ->lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'posted') {
                throw new Exception('سند صرف العميل مرحل مسبقاً.');
            }

            if ($voucher->status === 'cancelled') {
                throw new Exception('لا يمكن ترحيل سند صرف ملغى.');
            }

            $salesReturn = SalesReturn::lockForUpdate()
                ->findOrFail($voucher->sales_return_id);

            if ($salesReturn->status !== 'posted') {
                throw new Exception('لا يمكن الصرف على مردود مبيعات غير مرحل.');
            }

            $availableAmount = $this->availableRefundAmount($salesReturn);

            if ((float) $voucher->amount > $availableAmount) {
                throw new Exception(
                    'مبلغ الصرف أكبر من المبلغ المتاح. المتاح: ' . number_format($availableAmount, 2)
                );
            }

            $newRefundedAmount = round(
                (float) ($salesReturn->refunded_amount ?? 0) + (float) $voucher->amount,
                2
            );

            $salesReturn->update([
                'refunded_amount' => $newRefundedAmount,
            ]);

            $this->createJournalEntry($voucher);

            $voucher->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $voucher->fresh([
                'salesReturn',
                'customer',
                'branch',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | cancel
    |--------------------------------------------------------------------------
    | إلغاء سند صرف العميل:
    | - إنقاص refunded_amount من مردود المبيعات
    | - عكس القيود المحاسبية
    */
    public function cancel(CustomerRefundVoucher $voucher, ?string $reason = null): CustomerRefundVoucher
    {
        return DB::transaction(function () use ($voucher, $reason) {

            $voucher = CustomerRefundVoucher::with([
                    'salesReturn',
                    'customer',
                    'branch',
                ])
                ->lockForUpdate()
                ->findOrFail($voucher->id);

            if ($voucher->status === 'cancelled') {
                throw new Exception('سند صرف العميل ملغى مسبقاً.');
            }

            if ($voucher->status === 'draft') {
                $voucher->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ]);

                return $voucher->fresh();
            }

            $salesReturn = SalesReturn::lockForUpdate()
                ->findOrFail($voucher->sales_return_id);

            $newRefundedAmount = round(
                (float) ($salesReturn->refunded_amount ?? 0) - (float) $voucher->amount,
                2
            );

            if ($newRefundedAmount < 0) {
                $newRefundedAmount = 0;
            }

            $salesReturn->update([
                'refunded_amount' => $newRefundedAmount,
            ]);

            /*
                عكس القيود المحاسبية الخاصة بسند الصرف.
                JournalEntryService::reverse() عندك يستقبل JournalEntry Model.
            */
            $journalEntries = JournalEntry::where('reference_type', CustomerRefundVoucher::class)
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

            return $voucher->fresh([
                'salesReturn',
                'customer',
                'branch',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | createJournalEntry
    |--------------------------------------------------------------------------
    | قيد سند صرف العميل:
    |
    | من حـ / العملاء
    |     إلى حـ / الصندوق أو البنك
    */
    private function createJournalEntry(CustomerRefundVoucher $voucher): void
    {
        $receivableAccount = $this->accountId('accounts_receivable');
        $cashOrBankAccount = $this->cashOrBankAccount($voucher->payment_method);

        $this->journalEntryService->create([
            'entry_date' => $voucher->refund_date,
            'document_type' => 'customer_refund_voucher',
            'document_number' => $voucher->voucher_no,

            'reference_type' => CustomerRefundVoucher::class,
            'reference_id' => $voucher->id,

            'description' => 'سند صرف عميل رقم ' . $voucher->voucher_no,

            'lines' => [
                [
                    'account_id' => $receivableAccount,
                    'debit' => $voucher->amount,
                    'credit' => 0,
                    'description' => 'صرف مبلغ مستحق للعميل',
                    'customer_id' => $voucher->customer_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
                [
                    'account_id' => $cashOrBankAccount,
                    'debit' => 0,
                    'credit' => $voucher->amount,
                    'description' => 'صرف من الصندوق أو البنك',
                    'customer_id' => $voucher->customer_id,
                    'branch_id' => $voucher->branch_id,
                    'cost_center_id' => $voucher->cost_center_id,
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | availableRefundAmount
    |--------------------------------------------------------------------------
    | المبلغ المتاح للصرف من مردود المبيعات.
    */
    private function availableRefundAmount(SalesReturn $salesReturn): float
    {
        $refundableAmount = round((float) $salesReturn->refundable_amount, 2);
        $refundedAmount = round((float) ($salesReturn->refunded_amount ?? 0), 2);

        $availableAmount = round($refundableAmount - $refundedAmount, 2);

        return max($availableAmount, 0);
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


    private function generateVoucherNo(): string
    {
        return 'CRF-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }
}
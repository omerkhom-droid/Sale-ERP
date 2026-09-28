<?php

namespace App\Services;

use App\Models\SalesDebitNote;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;

class SalesDocumentBalanceService
{
    // Call while holding the original invoice's lock inside a transaction.
    public function netAmount(SalesInvoice $invoice, ?int $excludeReturnId = null): float
    {
        $debits = SalesDebitNote::where('sales_invoice_id', $invoice->id)
            ->where('status', 'posted')->sum('total_amount');
        $returns = SalesReturn::where('sales_invoice_id', $invoice->id)->where('status', 'posted');
        if ($excludeReturnId !== null) {
            $returns->where('id', '!=', $excludeReturnId);
        }
        // refundable_amount is settled separately by CustomerRefundVoucherService.
        // Subtracting it here too would consume the same credit twice.
        return round((float) $invoice->total_amount + (float) $debits - (float) $returns->sum('applied_amount'), 2);
    }

    public function refresh(SalesInvoice $invoice): void
    {
        $remaining = max(0, round($this->netAmount($invoice) - (float) $invoice->paid_amount, 2));
        $invoice->forceFill([
            'remaining_amount' => $remaining,
            'payment_status' => $remaining <= 0 ? 'paid' : ((float) $invoice->paid_amount > 0 ? 'partial' : 'unpaid'),
        ])->save();
    }
}

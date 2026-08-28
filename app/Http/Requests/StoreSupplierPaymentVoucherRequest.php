<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'save_action' => ['required', 'in:draft,post'],

            'voucher_no' => ['nullable', 'string', 'max:100', 'unique:supplier_payment_vouchers,voucher_no'],

            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer'],
            'voucher_date' => ['required', 'date'],
            'payment_account_id' => ['required', 'exists:accounts,id'],

            'amount' => ['required', 'numeric', 'min:0.01'],

            'notes' => ['nullable', 'string'],

            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.purchase_invoice_id' => ['required', 'exists:purchase_invoices,id'],
            'allocations.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
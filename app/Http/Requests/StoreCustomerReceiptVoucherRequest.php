<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerReceiptVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'save_action' => ['required', 'in:draft,post'],

            'voucher_no' => ['nullable', 'string', 'max:100', 'unique:customer_receipt_vouchers,voucher_no'],

            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer'],
            'receipt_date' => ['required', 'date'],

            'payment_method' => ['required', 'in:cash,card,bank_transfer,other'],

            'amount' => ['required', 'numeric', 'min:0.01'],

            'notes' => ['nullable', 'string', 'max:2000'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

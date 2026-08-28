<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'save_action' => ['required', 'in:draft,post'],
            
            'invoice_no' => ['nullable', 'string', 'max:100', 'unique:purchase_invoices,invoice_no'],
            
            'branch_id' => ['nullable', 'exists:branches,id'],

           'cost_center_id' => ['nullable', 'integer'],
           
            'supplier_id' => ['required', 'exists:suppliers,id'],

            'warehouse_id' => ['required', 'exists:warehouses,id'],

            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],

            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_account_id' => ['nullable', 'exists:accounts,id'],

            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $paidAmount = (float) $this->input('paid_amount', 0);

            if ($paidAmount > 0 && !$this->filled('payment_account_id')) {
                $validator->errors()->add(
                    'payment_account_id',
                    'يجب اختيار حساب الدفع عند وجود مبلغ مدفوع.'
                );
            }
        });
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesDebitNoteRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->can('sales_debit_notes.create'); }
    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'uuid'],
            'sales_invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'note_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:2000'],
            'save_action' => ['required', 'in:draft,post'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.sales_invoice_item_id' => ['required', 'integer', 'distinct'],
            'items.*.adjustment_type' => ['required', 'in:quantity,price'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,3'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:100000000', 'decimal:0,2'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => 'سبب الإشعار', 'note_date' => 'تاريخ الإشعار', 'items.*.quantity' => 'الكمية', 'items.*.unit_price' => 'سعر الزيادة'];
    }
}

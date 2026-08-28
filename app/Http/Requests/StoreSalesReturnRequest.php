<?php

namespace App\Http\Requests;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'save_action' => ['required', 'in:draft,post'],

            'return_no' => ['nullable', 'string', 'max:100', 'unique:sales_returns,return_no'],

            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],

            'return_date' => ['required', 'date'],

            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],

            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }


    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $invoiceId = (int) $this->input('sales_invoice_id');

            $invoice = SalesInvoice::with([
                    'items.returnItems.salesReturn',
                ])
                ->find($invoiceId);

            if (!$invoice) {
                return;
            }

            if ($invoice->status !== 'posted') {
                $validator->errors()->add(
                    'sales_invoice_id',
                    'لا يمكن عمل مردود إلا على فاتورة بيع مرحلة.'
                );

                return;
            }

            if ($invoice->status === 'cancelled') {
                $validator->errors()->add(
                    'sales_invoice_id',
                    'لا يمكن عمل مردود على فاتورة ملغاة.'
                );

                return;
            }

            $hasReturnQuantity = false;

            foreach ($this->input('items', []) as $index => $row) {

                $invoiceItemId = $row['sales_invoice_item_id'] ?? null;
                $returnQty = (float) ($row['quantity'] ?? 0);

                if ($returnQty <= 0) {
                    continue;
                }

                $hasReturnQuantity = true;

                $invoiceItem = SalesInvoiceItem::with([
                        'returnItems.salesReturn',
                    ])
                    ->find($invoiceItemId);

                if (!$invoiceItem) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | التأكد أن البند يخص نفس فاتورة البيع
                |--------------------------------------------------------------------------
                */
                if ((int) $invoiceItem->sales_invoice_id !== $invoiceId) {
                    $validator->errors()->add(
                        "items.$index.sales_invoice_item_id",
                        'البند لا يخص فاتورة البيع المحددة.'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | حساب الكمية المرتجعة سابقًا
                |--------------------------------------------------------------------------
                | نحسب فقط المردودات المرحلة.
                */
                $previousReturnedQty = $invoiceItem->returnItems
                    ->filter(function ($returnItem) {
                        return $returnItem->salesReturn
                            && $returnItem->salesReturn->status === 'posted';
                    })
                    ->sum('quantity');

                $availableQty = round(
                    (float) $invoiceItem->quantity - (float) $previousReturnedQty,
                    3
                );

                if ($returnQty > $availableQty) {
                    $validator->errors()->add(
                        "items.$index.quantity",
                        'كمية المردود أكبر من الكمية المتاحة للإرجاع. المتاح: ' . $availableQty
                    );
                }
            }

            if (!$hasReturnQuantity) {
                $validator->errors()->add(
                    'items',
                    'يجب إدخال كمية مردود لصنف واحد على الأقل.'
                );
            }
        });
    }


    public function messages(): array
    {
        return [
            'save_action.required' => 'نوع الحفظ مطلوب.',
            'save_action.in' => 'نوع الحفظ غير صحيح.',

            'sales_invoice_id.required' => 'فاتورة البيع مطلوبة.',
            'sales_invoice_id.exists' => 'فاتورة البيع غير موجودة.',

            'return_date.required' => 'تاريخ المردود مطلوب.',
            'return_date.date' => 'تاريخ المردود غير صحيح.',

            'items.required' => 'أصناف المردود مطلوبة.',
            'items.array' => 'صيغة الأصناف غير صحيحة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل.',

            'items.*.sales_invoice_item_id.required' => 'بند الفاتورة مطلوب.',
            'items.*.sales_invoice_item_id.exists' => 'بند الفاتورة غير موجود.',

            'items.*.quantity.required' => 'كمية المردود مطلوبة.',
            'items.*.quantity.numeric' => 'كمية المردود يجب أن تكون رقم.',
            'items.*.quantity.min' => 'كمية المردود لا يمكن أن تكون أقل من صفر.',
        ];
    }
}
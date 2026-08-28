<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | authorize
    |--------------------------------------------------------------------------
    | true معناها اسمح للطلب يمر.
    | لاحقاً عندما نضيف صلاحيات يمكن نربطها بـ Policies.
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | rules
    |--------------------------------------------------------------------------
    | هنا نتحقق من البيانات القادمة من صفحة مردود المشتريات.
    |
    | ملاحظة:
    | التحقق العميق مثل:
    | - هل الفاتورة مرحلة؟
    | - هل الكمية المرتجعة لا تتجاوز المتاح؟
    |
    | سنعمله في Service، لأنه يحتاج استعلامات وحسابات.
    */
    public function rules(): array
    {
        return [
            /*
                المستخدم يختار:
                draft = حفظ مسودة
                post  = حفظ وترحيل
            */
            'save_action' => ['required', 'in:draft,post'],

            /*
                return_no يمكن تركه فارغاً ليقوم النظام بتوليده.
            */
            'return_no' => ['nullable', 'string', 'max:100', 'unique:purchase_returns,return_no'],

            /*
                المردود لازم يكون مربوط بفاتورة مشتريات.
            */
            'purchase_invoice_id' => ['required', 'exists:purchase_invoices,id'],

            'return_date' => ['required', 'date'],

            'notes' => ['nullable', 'string'],

            /*
                items هي الأصناف المرتجعة.
            */
            'items' => ['required', 'array', 'min:1'],

            /*
                كل سطر مردود لازم يكون مربوط بسطر من فاتورة المشتريات.
            */
            'items.*.purchase_invoice_item_id' => ['required', 'exists:purchase_invoice_items,id'],

            /*
                الكمية المرتجعة.
                يجب أن تكون أكبر من صفر.
            */
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSalesInvoiceRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | authorize
    |--------------------------------------------------------------------------
    | حالياً نسمح لأي مستخدم مسجل.
    | لاحقاً نربطها بالصلاحيات.
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | rules
    |--------------------------------------------------------------------------
    | قواعد التحقق الأساسية لفاتورة البيع.
    */
    public function rules(): array
    {
        return [
            /*
                save_action:
                draft = حفظ مسودة
                post  = حفظ وترحيل مباشرة
            */
            'save_action' => ['required', 'in:draft,post'],

            /*
                رقم الفاتورة اختياري.
                إذا لم يرسله المستخدم سيولده النظام.
            */
            'invoice_no' => ['nullable', 'string', 'max:100', 'unique:sales_invoices,invoice_no'],

            /*
                نوع العميل:
                cash   = عميل نقدي
                credit = عميل آجل
            */
            'customer_type' => ['required', 'in:cash,credit'],

            /*
                العميل اختياري في حالة النقدي.
                مطلوب في حالة الآجل أو الجزئي.
            */
            'customer_id' => ['nullable', 'exists:customers,id'],

            /*
                بيانات العميل النقدي أو نسخة من العميل المسجل.
            */
            'customer_name' => ['nullable', 'string', 'max:255'],

            'customer_mobile' => ['nullable', 'string', 'max:50'],

            'customer_tax_number' => ['nullable', 'string', 'max:50'],

            'customer_address' => ['nullable', 'string'],

            /*
                نوع الدفع:
                cash    = مدفوعة بالكامل
                credit  = آجلة بالكامل
                partial = جزء مدفوع وجزء آجل
            */
            'payment_type' => ['required', 'in:cash,credit,partial'],

            /*
                طريقة الدفع للمبلغ المدفوع.
            */
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer,other'],

            'branch_id' => ['nullable', 'exists:branches,id'],
            
            'cost_center_id' => ['nullable', 'integer'],

            'warehouse_id' => ['required', 'exists:warehouses,id'],

            'invoice_date' => ['required', 'date'],

            'paid_amount' => ['nullable', 'numeric', 'min:0'],

            'notes' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | أصناف الفاتورة
            |--------------------------------------------------------------------------
            */
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', 'exists:products,id'],

            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],

            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],

            'items.*.unit_price' => ['required', 'numeric', 'min:0'],

            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],

            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | withValidator
    |--------------------------------------------------------------------------
    | تحقق إضافي حسب نوع العميل ونوع الدفع.
    */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $customerType = $this->input('customer_type');
            $paymentType = $this->input('payment_type');
            $paymentMethod = $this->input('payment_method');
            $customerId = $this->input('customer_id');
            $paidAmount = (float) $this->input('paid_amount', 0);

            /*
            |--------------------------------------------------------------------------
            | العميل الآجل يجب أن يكون محفوظاً
            |--------------------------------------------------------------------------
            | لأن الآجل يحتاج كشف حساب وذمم مدينة.
            */
            if ($customerType === 'credit' && empty($customerId)) {
                $validator->errors()->add(
                    'customer_id',
                    'العميل الآجل يجب اختياره من قائمة العملاء.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | الفاتورة الآجلة أو الجزئية يجب أن تكون مرتبطة بعميل محفوظ
            |--------------------------------------------------------------------------
            | لأن هناك مبلغ متبقي على العميل.
            */
            if (in_array($paymentType, ['credit', 'partial'], true) && empty($customerId)) {
                $validator->errors()->add(
                    'customer_id',
                    'الفاتورة الآجلة أو الجزئية يجب أن تكون مرتبطة بعميل محفوظ.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | إذا يوجد مبلغ مدفوع، يجب تحديد طريقة الدفع
            |--------------------------------------------------------------------------
            */
            if ($paidAmount > 0 && empty($paymentMethod)) {
                $validator->errors()->add(
                    'payment_method',
                    'يرجى تحديد طريقة الدفع للمبلغ المدفوع.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | إذا نوع الدفع نقدي بالكامل
            |--------------------------------------------------------------------------
            | لا نسمح أن يكون customer_type = credit.
            */
            if ($paymentType === 'cash' && $customerType === 'credit') {
                $validator->errors()->add(
                    'customer_type',
                    'إذا كانت الفاتورة نقدية بالكامل، اجعل نوع العميل نقدي.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | الفاتورة الجزئية يجب أن تحتوي مبلغ مدفوع
            |--------------------------------------------------------------------------
            */
            if ($paymentType === 'partial' && $paidAmount <= 0) {
                $validator->errors()->add(
                    'paid_amount',
                    'في الفاتورة الجزئية يجب إدخال مبلغ مدفوع.'
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'save_action.required' => 'يرجى تحديد نوع الحفظ.',
            'save_action.in' => 'نوع الحفظ غير صحيح.',

            'customer_type.required' => 'يرجى تحديد نوع العميل.',
            'customer_type.in' => 'نوع العميل غير صحيح.',

            'payment_type.required' => 'يرجى تحديد نوع الدفع.',
            'payment_type.in' => 'نوع الدفع غير صحيح.',

            'warehouse_id.required' => 'يرجى اختيار المستودع.',
            'warehouse_id.exists' => 'المستودع المحدد غير موجود.',

            'invoice_date.required' => 'يرجى تحديد تاريخ الفاتورة.',
            'invoice_date.date' => 'تاريخ الفاتورة غير صحيح.',

            'items.required' => 'يرجى إضافة صنف واحد على الأقل.',
            'items.min' => 'يرجى إضافة صنف واحد على الأقل.',

            'items.*.product_id.required' => 'يوجد سطر بدون صنف.',
            'items.*.product_id.exists' => 'يوجد صنف غير صحيح.',

            'items.*.product_unit_id.required' => 'يوجد سطر بدون وحدة.',
            'items.*.product_unit_id.exists' => 'يوجد وحدة غير صحيحة.',

            'items.*.quantity.required' => 'يرجى إدخال الكمية.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',

            'items.*.unit_price.required' => 'يرجى إدخال سعر البيع.',
            'items.*.unit_price.min' => 'سعر البيع لا يمكن أن يكون أقل من صفر.',
        ];
    }
}
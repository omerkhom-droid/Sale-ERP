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
            'customer_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_mobile' => [
                'nullable',
                'regex:/^\+?[0-9]{8,15}$/',
            ],

            'customer_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'customer_tax_number' => [
                'nullable',
                'regex:/^3[0-9]{13}3$/',
            ],

            'customer_commercial_register' => [
                'nullable',
                'digits:10',
            ],

            'customer_country_code' => [
                'nullable',
                'regex:/^[A-Za-z]{2}$/',
            ],

            'customer_state' => [
                'nullable',
                'string',
                'max:127',
            ],

            'customer_city' => [
                'nullable',
                'string',
                'max:127',
            ],

            'customer_district' => [
                'nullable',
                'string',
                'max:127',
            ],

            'customer_street_name' => [
                'nullable',
                'string',
                'max:127',
            ],

            'customer_building_number' => [
                'nullable',
                'digits:4',
            ],

            'customer_additional_number' => [
                'nullable',
                'digits:4',
            ],

            'customer_postal_code' => [
                'nullable',
                'digits:5',
            ],

            'customer_address' => [
                'nullable',
                'string',
                'max:1000',
            ],

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

            $standardInvoiceFields = [
                'customer_tax_number',
                'customer_commercial_register',
                'customer_country_code',
                'customer_state',
                'customer_city',
                'customer_district',
                'customer_street_name',
                'customer_building_number',
                'customer_additional_number',
                'customer_postal_code',
            ];

            $hasStandardInvoiceData = collect($standardInvoiceFields)
                ->contains(function (string $field) {
                    return filled($this->input($field));
                });

            if ($hasStandardInvoiceData) {
                $requiredStandardFields = [
                    'customer_name' => 'اسم العميل',
                    'customer_country_code' => 'الدولة',
                    'customer_city' => 'المدينة',
                    'customer_district' => 'الحي',
                    'customer_street_name' => 'اسم الشارع',
                    'customer_building_number' => 'رقم المبنى',
                    'customer_postal_code' => 'الرمز البريدي',
                ];

                foreach ($requiredStandardFields as $field => $label) {
                    if (blank($this->input($field))) {
                        $validator->errors()->add(
                            $field,
                            "حقل {$label} مطلوب لإصدار فاتورة قياسية."
                        );
                    }
                }

                if (
                    blank($this->input('customer_tax_number'))
                    && blank($this->input('customer_commercial_register'))
                ) {
                    $validator->errors()->add(
                        'customer_tax_number',
                        'للفاتورة القياسية يجب إدخال الرقم الضريبي أو السجل التجاري للعميل.'
                    );
                }
            }

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

            'customer_mobile.regex' =>
                'رقم جوال العميل غير صحيح، ويجب أن يحتوي من 8 إلى 15 رقماً.',

            'customer_email.email' =>
                'البريد الإلكتروني للعميل غير صحيح.',

            'customer_tax_number.regex' =>
                'الرقم الضريبي يجب أن يتكون من 15 رقماً ويبدأ وينتهي بالرقم 3.',

            'customer_commercial_register.digits' =>
                'السجل التجاري يجب أن يتكون من 10 أرقام.',

            'customer_country_code.regex' =>
                'رمز الدولة يجب أن يتكون من حرفين، مثل SA.',

            'customer_building_number.digits' =>
                'رقم المبنى يجب أن يتكون من 4 أرقام.',

            'customer_additional_number.digits' =>
                'الرقم الإضافي يجب أن يتكون من 4 أرقام.',

            'customer_postal_code.digits' =>
                'الرمز البريدي يجب أن يتكون من 5 أرقام.',
                
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
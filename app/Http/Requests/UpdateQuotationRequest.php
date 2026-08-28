<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $quotation = $this->route('quotation');
        $quotationId = is_object($quotation) ? $quotation->id : $quotation;

        return [
            'quotation_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('quotations', 'quotation_no')->ignore($quotationId),
            ],

            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],

            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],

            'customer_type' => ['required', Rule::in(['cash', 'credit'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],

            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:50'],
            'customer_tax_number' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string', 'max:1000'],

            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],

            'save_action' => ['nullable', Rule::in(['draft', 'sent'])],

            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_unit_id' => ['required', 'integer', 'exists:product_units,id'],

            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],

            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'يجب اختيار الفرع.',
            'branch_id.exists' => 'الفرع المحدد غير صحيح.',

            'warehouse_id.exists' => 'المستودع المحدد غير صحيح.',
            'cost_center_id.exists' => 'مركز التكلفة المحدد غير صحيح.',

            'quotation_date.required' => 'تاريخ عرض السعر مطلوب.',
            'valid_until.after_or_equal' => 'تاريخ الصلاحية يجب أن يكون بعد أو يساوي تاريخ عرض السعر.',

            'customer_type.required' => 'نوع العميل مطلوب.',
            'customer_type.in' => 'نوع العميل غير صحيح.',
            'customer_id.exists' => 'العميل المحدد غير صحيح.',

            'items.required' => 'يجب إضافة صنف واحد على الأقل.',
            'items.array' => 'بيانات الأصناف غير صحيحة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل.',

            'items.*.product_id.required' => 'يجب اختيار الصنف.',
            'items.*.product_id.exists' => 'أحد الأصناف المحددة غير صحيح.',

            'items.*.product_unit_id.required' => 'يجب اختيار الوحدة.',
            'items.*.product_unit_id.exists' => 'إحدى الوحدات المحددة غير صحيحة.',

            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',

            'items.*.unit_price.required' => 'سعر البيع مطلوب.',
            'items.*.unit_price.min' => 'سعر البيع لا يمكن أن يكون أقل من صفر.',

            'items.*.discount_amount.min' => 'الخصم لا يمكن أن يكون أقل من صفر.',
            'items.*.vat_rate.max' => 'نسبة الضريبة غير صحيحة.',
        ];
    }
}
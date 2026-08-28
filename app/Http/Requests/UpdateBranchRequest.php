<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * تجهيز البيانات قبل التحقق.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper($this->country_code ?? ''),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer'],

            'branch_name' => ['required', 'string', 'max:255'],

            'preceatage' => ['required', 'numeric', 'min:0', 'max:100'],

            'tax_registration_number' => ['required', 'digits:15'],

            'license_type' => ['required', 'string', 'max:50'],

            'license_number' => ['required', 'digits:10'],

            'country_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'state' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'additional_street_name' => ['required', 'string', 'max:255'],

            'building_number' => ['required', 'digits_between:4,5'],
            'secondary_number' => ['required', 'digits:4'],
            'postal_zone' => ['required', 'digits:5'],

            'phone' => ['required', 'string', 'max:50'],
            'details' => ['nullable', 'string'],
            'is_active' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * رسائل التحقق المخصصة.
     */
    public function messages(): array
    {
        return [
            'country_code.required' => 'رمز الدولة مطلوب.',
            'country_code.size' => 'رمز الدولة يجب أن يتكون من 3 أحرف مثل KSA.',
            'country_code.regex' => 'رمز الدولة يجب أن يحتوي على أحرف إنجليزية كبيرة فقط مثل KSA.',

            'tax_registration_number.required' => 'الرقم الضريبي مطلوب.',
            'tax_registration_number.digits' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا.',

            'license_number.required' => 'رقم الترخيص مطلوب.',
            'license_number.digits' => 'رقم الترخيص يجب أن يتكون من 10 أرقام.',

            'building_number.required' => 'رقم المبنى مطلوب.',
            'building_number.digits_between' => 'رقم المبنى يجب أن يكون من 4 إلى 5 أرقام.',

            'secondary_number.required' => 'الرقم الفرعي مطلوب.',
            'secondary_number.digits' => 'الرقم الفرعي يجب أن يتكون من 4 أرقام.',

            'postal_zone.required' => 'الرمز البريدي مطلوب.',
            'postal_zone.digits' => 'الرمز البريدي يجب أن يتكون من 5 أرقام.',
        ];
    }

    /**
     * أسماء الحقول بالعربي.
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'الشركة',
            'branch_name' => 'اسم الفرع',
            'preceatage' => 'النسبة',
            'tax_registration_number' => 'الرقم الضريبي',
            'license_type' => 'نوع الترخيص',
            'license_number' => 'رقم الترخيص',
            'country_code' => 'رمز الدولة',
            'state' => 'المنطقة',
            'city' => 'المدينة',
            'neighborhood' => 'الحي',
            'street_name' => 'اسم الشارع',
            'additional_street_name' => 'اسم الشارع الإضافي',
            'building_number' => 'رقم المبنى',
            'secondary_number' => 'الرقم الفرعي',
            'postal_zone' => 'الرمز البريدي',
            'phone' => 'رقم الهاتف',
            'details' => 'التفاصيل',
            'is_active' => 'الحالة',
        ];
    }
}
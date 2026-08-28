<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')->id;

        return [
            'customer_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('customers', 'customer_code')->ignore($customerId),
            ],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_type' => ['required', 'in:individual,company'],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],

            'tax_number' => ['nullable', 'digits:15'],
            'commercial_register' => ['nullable', 'string', 'max:20'],

            'country_code' => ['nullable', 'string', 'max:10'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:10'],
            'additional_number' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'address' => ['nullable', 'string'],

            'opening_balance' => ['required', 'numeric', 'min:0'],
            'balance_type' => ['required', 'in:debit,credit'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
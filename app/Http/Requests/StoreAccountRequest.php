<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:accounts,id'],

            'account_code' => [
                'required',
                'string',
                'max:50',
                'unique:accounts,account_code'
            ],

            'account_name_ar' => [
                'required',
                'string',
                'max:255'
            ],

            'account_name_en' => [
                'nullable',
                'string',
                'max:255'
            ],

            'account_type' => [
                'required',
                'in:asset,liability,equity,revenue,expense'
            ],

            'normal_balance' => [
                'required',
                'in:debit,credit'
            ],

            'is_group' => [
                'required',
                'boolean'
            ],

            'is_active' => [
                'required',
                'boolean'
            ],
        ];
    }
}

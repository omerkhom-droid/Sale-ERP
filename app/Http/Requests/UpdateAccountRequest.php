<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('account')?->id;

        return [
            'parent_id' => ['nullable', 'exists:accounts,id'],

            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts', 'account_code')->ignore($accountId),
            ],

            'account_name_ar' => ['required', 'string', 'max:255'],
            'account_name_en' => ['nullable', 'string', 'max:255'],

            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['required', 'in:debit,credit'],

            'is_group' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
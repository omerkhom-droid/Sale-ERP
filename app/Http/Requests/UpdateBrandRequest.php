<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('brand')->id;

        return [
            'brand_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'brand_name')->ignore($brandId),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
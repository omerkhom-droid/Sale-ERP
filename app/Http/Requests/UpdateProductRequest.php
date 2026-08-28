<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->route('product')->id),
            ],
            'product_name_ar' => ['required', 'string', 'max:255'],
            'product_name_en' => ['nullable', 'string', 'max:255'],
            'short_name' => ['nullable','string','max:100'],
            'keywords' => ['nullable','string'],
            'product_type' => ['required','in:inventory,service'],
            'description' => ['nullable', 'string'],
            
            'minimum_quantity' => ['required', 'numeric', 'min:0'],
            'track_inventory' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],

            'units' => ['required', 'array', 'min:1'],
            'units.*.unit_id' => ['required', 'exists:units,id'],
            'units.*.factor' => ['required', 'numeric', 'min:0.001'],
            'units.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'units.*.sale_price' => ['required', 'numeric', 'min:0'],
            'units.*.minimum_sale_price' => ['required', 'numeric', 'min:0'],
            'units.*.is_default' => ['required', 'boolean'],

            'units.*.barcode' => [
                'nullable',
                'string',
                'max:100',
                'distinct',
            ],
            
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            
            'show_in_pos' => ['nullable', 'boolean'],
            'pos_sort_order' => ['nullable', 'integer', 'min:0'],
            'pos_button_color' => ['nullable', 'string', 'max:20'],
        ];
    }
}

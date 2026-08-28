<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'warehouse_code' => ['required', 'string', 'max:50', 'unique:warehouses,warehouse_code'],
            'warehouse_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
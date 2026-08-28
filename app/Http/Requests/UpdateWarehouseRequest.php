<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')->id;

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'warehouse_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'warehouse_code')->ignore($warehouseId),
            ],
            'warehouse_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')->id;

        return [
            'unit_name' => ['required', 'string', 'max:255'],
            'unit_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'unit_code')->ignore($unitId),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
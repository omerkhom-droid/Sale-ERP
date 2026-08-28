<?php

namespace App\Http\Requests;

use App\Models\SalesReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCustomerRefundVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'save_action' => ['required', 'in:draft,post'],

            'voucher_no' => [
                'nullable',
                'string',
                'max:100',
                'unique:customer_refund_vouchers,voucher_no',
            ],

            'sales_return_id' => [
                'required',
                'exists:sales_returns,id',
            ],

            'refund_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                'in:cash,card,bank_transfer,other',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $salesReturnId = (int) $this->input('sales_return_id');
            $amount = round((float) $this->input('amount', 0), 2);

            $salesReturn = SalesReturn::find($salesReturnId);

            if (!$salesReturn) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | لا يتم الصرف إلا على مردود مبيعات مرحل
            |--------------------------------------------------------------------------
            */
            if ($salesReturn->status !== 'posted') {
                $validator->errors()->add(
                    'sales_return_id',
                    'لا يمكن صرف مبلغ للعميل إلا على مردود مبيعات مرحل.'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | لا يتم الصرف إذا لا يوجد مبلغ مستحق للعميل
            |--------------------------------------------------------------------------
            */
            $refundableAmount = round((float) $salesReturn->refundable_amount, 2);
            $refundedAmount = round((float) ($salesReturn->refunded_amount ?? 0), 2);

            $availableAmount = round($refundableAmount - $refundedAmount, 2);

            if ($availableAmount <= 0) {
                $validator->errors()->add(
                    'sales_return_id',
                    'لا يوجد مبلغ متاح للصرف لهذا المردود.'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | لا يسمح بصرف أكثر من المتاح
            |--------------------------------------------------------------------------
            */
            if ($amount > $availableAmount) {
                $validator->errors()->add(
                    'amount',
                    'مبلغ الصرف أكبر من المبلغ المتاح. المتاح: ' . number_format($availableAmount, 2)
                );
            }
        });
    }


    public function messages(): array
    {
        return [
            'save_action.required' => 'نوع الحفظ مطلوب.',
            'save_action.in' => 'نوع الحفظ غير صحيح.',

            'sales_return_id.required' => 'مردود المبيعات مطلوب.',
            'sales_return_id.exists' => 'مردود المبيعات غير موجود.',

            'refund_date.required' => 'تاريخ سند الصرف مطلوب.',
            'refund_date.date' => 'تاريخ سند الصرف غير صحيح.',

            'payment_method.required' => 'طريقة الصرف مطلوبة.',
            'payment_method.in' => 'طريقة الصرف غير صحيحة.',

            'amount.required' => 'مبلغ الصرف مطلوب.',
            'amount.numeric' => 'مبلغ الصرف يجب أن يكون رقم.',
            'amount.min' => 'مبلغ الصرف يجب أن يكون أكبر من صفر.',
        ];
    }
}
<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'in:to_supplier'],
            'supplier_id' => ['sometimes', 'nullable', Rule::exists('suppliers', 'id')->where('user_id', $this->user()->id)],
            'purchase_id' => ['sometimes', 'nullable', Rule::exists('purchases', 'id')->where('user_id', $this->user()->id)],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'payment_method' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'نوع الدفع غير صالح.',

            'supplier_id.exists' => 'المورد غير موجود.',

            'purchase_id.exists' => 'عملية الشراء غير موجودة.',

            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من 0.',

            'payment_date.date' => 'تاريخ الدفع غير صالح.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

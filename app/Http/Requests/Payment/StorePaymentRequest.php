<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Support\Facades\Auth;

class StorePaymentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:to_supplier'],
            'supplier_id' => ['nullable', 'required_if:type,to_supplier', 'exists:suppliers,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الدفع مطلوب.',
            'type.in' => 'نوع الدفع غير صالح.',

            'supplier_id.required_if' => 'المورد مطلوب عند اختيار الدفع للمورد.',
            'supplier_id.exists' => 'المورد غير موجود.',

            'purchase_id.exists' => 'عملية الشراء غير موجودة.',

            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من أو يساوي 0.',

            'payment_date.required' => 'تاريخ الدفع مطلوب.',
            'payment_date.date' => 'تاريخ الدفع غير صالح.',

            'payment_method.required' => 'طريقة الدفع مطلوبة.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:to_supplier,from_customer'],
            'supplier_id' => ['nullable', 'required_if:type,to_supplier', Rule::exists('suppliers', 'id')->where('user_id', $this->user()->id)],
            'purchase_id' => ['nullable', 'required_if:type,to_supplier', Rule::exists('purchases', 'id')->where('user_id', $this->user()->id)],
            'customer_id' => ['nullable', 'required_if:type,from_customer', Rule::exists('customers', 'id')->where('user_id', $this->user()->id)],
            'sale_id' => ['nullable', 'required_if:type,from_customer', Rule::exists('sales', 'id')->where('user_id', $this->user()->id)],
            'amount' => ['required', 'numeric', 'gt:0'],
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

            'purchase_id.required_if' => 'الشراء مطلوب عند اختيار الدفع للمورد.',
            'purchase_id.exists' => 'عملية الشراء غير موجودة.',

            'customer_id.required_if' => 'العميل مطلوب عند اختيار التحصيل من العميل.',
            'customer_id.exists' => 'العميل غير موجود.',

            'sale_id.required_if' => 'البيع مطلوب عند اختيار التحصيل من العميل.',
            'sale_id.exists' => 'عملية البيع غير موجودة.',

            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من 0.',

            'payment_date.required' => 'تاريخ الدفع مطلوب.',
            'payment_date.date' => 'تاريخ الدفع غير صالح.',

            'payment_method.required' => 'طريقة الدفع مطلوبة.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

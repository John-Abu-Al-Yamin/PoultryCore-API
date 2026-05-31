<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('user_id', $this->user()->id),
            ],
            'batch_id' => [
                'required',
                Rule::exists('batches', 'id')->where('user_id', $this->user()->id),
            ],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:cash,credit'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'المورد مطلوب.',
            'supplier_id.exists' => 'المورد غير موجود.',

            'batch_id.required' => 'الدفعة مطلوبة.',
            'batch_id.exists' => 'الدفعة غير موجودة.',

            'item_name.required' => 'اسم العنصر مطلوب.',

            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.min' => 'الكمية يجب أن تكون أكبر من أو تساوي 1.',

            'unit_price.required' => 'سعر الوحدة مطلوب.',
            'unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقمًا.',
            'unit_price.min' => 'سعر الوحدة يجب أن يكون أكبر من أو يساوي 0.',

            'purchase_date.required' => 'تاريخ الشراء مطلوب.',
            'purchase_date.date' => 'تاريخ الشراء غير صالح.',

            'payment_type.required' => 'نوع الدفع مطلوب.',
            'payment_type.in' => 'نوع الدفع يجب أن يكون cash أو credit.',
        ];
    }
}

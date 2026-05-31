<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'required', Rule::exists('suppliers', 'id')->where('user_id', $this->user()->id)],
            'batch_id' => ['sometimes', 'required', Rule::exists('batches', 'id')->where('user_id', $this->user()->id)],
            'item_name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'purchase_date' => ['sometimes', 'required', 'date'],
            'payment_type' => ['sometimes', 'required', 'in:cash,credit'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'المورد غير موجود.',
            'batch_id.exists' => 'الدفعة غير موجودة.',

            'item_name.required' => 'اسم العنصر مطلوب.',
            'item_name.string' => 'اسم العنصر يجب أن يكون نصًا.',
            'item_name.max' => 'اسم العنصر لا يمكن أن يتجاوز 255 حرفًا.',

            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.min' => 'الكمية يجب أن تكون أكبر من أو تساوي 1.',

            'unit_price.required' => 'سعر الوحدة مطلوب.',
            'unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقمًا.',
            'unit_price.min' => 'سعر الوحدة يجب أن يكون أكبر من أو يساوي 0.',

            'purchase_date.required' => 'تاريخ الشراء مطلوب.',
            'purchase_date.date' => 'تاريخ الشراء غير صالح.',
        ];
    }
}

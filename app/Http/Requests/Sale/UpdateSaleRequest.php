<?php

namespace App\Http\Requests\Sale;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'required', Rule::exists('customers', 'id')->where('user_id', $this->user()->id)],
            'batch_id' => ['sometimes', 'required', Rule::exists('batches', 'id')->where('user_id', $this->user()->id)],
            'item_name' => ['sometimes', 'required', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_date' => ['sometimes', 'required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'العميل غير موجود.',
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

            'sale_date.required' => 'تاريخ البيع مطلوب.',
            'sale_date.date' => 'تاريخ البيع غير صالح.',
        ];
    }
}

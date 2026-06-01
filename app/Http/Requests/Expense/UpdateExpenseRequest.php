<?php

namespace App\Http\Requests\Expense;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_id' => [
                'sometimes',
                'required',
                Rule::exists('batches', 'id')->where('user_id', $this->user()->id),
            ],
            'type' => ['sometimes', 'required', Rule::in(['feed', 'treatment', 'utilities', 'labor', 'maintenance', 'transport', 'other'])],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'date' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'batch_id.exists' => 'الدفعة غير موجودة.',

            'type.required' => 'نوع المصروف مطلوب.',
            'type.in' => 'نوع المصروف غير صالح.',

            'amount.required' => 'المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقمًا.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من أو يساوي 0.',

            'date.required' => 'التاريخ مطلوب.',
            'date.date' => 'التاريخ غير صالح.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 1000 حرف.',
        ];
    }
}

<?php

namespace App\Http\Requests\Death;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreDeathRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'batch_id' => 'required|exists:batches,id',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'حقل المستخدم مطلوب.',
            'user_id.exists' => 'المستخدم المحدد غير موجود.',

            'batch_id.required' => 'حقل الدفعة (Batch) مطلوب.',
            'batch_id.exists' => 'الدفعة المحددة غير موجودة.',

            'quantity.required' => 'حقل الكمية مطلوب.',
            'quantity.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'quantity.min' => 'الكمية يجب أن تكون على الأقل 1.',

            'date.required' => 'حقل التاريخ مطلوب.',
            'date.date' => 'يجب إدخال تاريخ صحيح.',

            'reason.string' => 'سبب النفوق يجب أن يكون نصًا.',
            'reason.max' => 'سبب النفوق يجب ألا يتجاوز 255 حرفًا.',
        ];
    }
}

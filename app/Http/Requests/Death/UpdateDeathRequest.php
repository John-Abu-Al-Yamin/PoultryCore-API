<?php

namespace App\Http\Requests\Death;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;


class UpdateDeathRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            "user_id" => "sometimes|required|exists:users,id",
            "batch_id" => "sometimes|required|exists:batches,id",
            "quantity" => "sometimes|required|integer|min:1",
            "date" => "sometimes|required|date",
            "reason" => "nullable|string|max:255",
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
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

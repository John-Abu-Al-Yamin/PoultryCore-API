<?php

namespace App\Http\Requests\Batch;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barn_id' => ['sometimes', 'integer', 'exists:barns,id'],
            'poultry_type' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'barn_id.integer' => 'معرّف العنبر يجب أن يكون رقمًا صحيحًا.',
            'barn_id.exists' => 'العنبر المحدد غير موجود.',

            'poultry_type.string' => 'نوع الدواجن يجب أن يكون نصًا.',
            'poultry_type.max' => 'نوع الدواجن يجب ألا يزيد عن 255 حرف.',


            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخًا صحيحًا.',

            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخًا صحيحًا.',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

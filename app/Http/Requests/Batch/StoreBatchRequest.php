<?php

namespace App\Http\Requests\Batch;

use App\Http\Requests\BaseApiRequest;
use App\Models\Barn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barn_id' => [
                'required',
                'integer',
                Rule::exists('barns', 'id')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'poultry_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }


    public function messages(): array
    {
        return [
            'barn_id.required' => 'معرّف العنبر مطلوب.',
            'barn_id.integer' => 'معرّف العنبر يجب أن يكون رقمًا صحيحًا.',
            'barn_id.exists' => 'العنبر المحدد غير موجود.',

            'poultry_type.required' => 'نوع الدواجن مطلوب.',
            'poultry_type.string' => 'نوع الدواجن يجب أن يكون نصًا.',
            'poultry_type.max' => 'نوع الدواجن يجب ألا يزيد عن 255 حرف.',

            'start_date.required' => 'تاريخ البداية مطلوب.',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخًا صحيحًا.',

            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخًا صحيحًا.',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

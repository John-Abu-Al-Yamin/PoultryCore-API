<?php

namespace App\Http\Requests\Barn;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreBarnRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('barns')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                }),
            ],

            'location' => ['nullable', 'string', 'max:255'],

            'capacity' => ['nullable', 'integer', 'min:0'],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الحظيرة مطلوب.',
            'name.string' => 'اسم الحظيرة يجب أن يكون نصًا.',
            'name.max' => 'اسم الحظيرة لا يمكن أن يتجاوز 255 حرفًا.',
            'name.unique' => 'يوجد بالفعل حظيرة بهذا الاسم لديك.',

            'location.string' => 'موقع الحظيرة يجب أن يكون نصًا.',
            'location.max' => 'موقع الحظيرة لا يمكن أن يتجاوز 255 حرفًا.',

            'capacity.integer' => 'سعة الحظيرة يجب أن تكون عددًا صحيحًا.',
            'capacity.min' => 'سعة الحظيرة لا يمكن أن تكون أقل من 0.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

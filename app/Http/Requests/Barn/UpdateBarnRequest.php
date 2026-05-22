<?php

namespace App\Http\Requests\Barn;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateBarnRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('barns')
                    ->where(fn($q) => $q->where('user_id', auth()->id()))
                    ->ignore($this->route('id')),
            ],

            'location' => ['sometimes', 'nullable', 'string', 'max:255'],

            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'notes' => ['sometimes', 'nullable', 'string'],
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

<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends BaseApiRequest
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
                Rule::unique('suppliers')
                    ->where(fn($q) => $q->where('user_id', auth()->id()))
                    ->ignore($this->route('id')),
            ],
            'phone' => ['sometimes', 'nullable', 'regex:/^01[0-9]{9}$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المورد مطلوب.',
            'name.string' => 'اسم المورد يجب أن يكون نصًا.',
            'name.max' => 'اسم المورد لا يمكن أن يتجاوز 255 حرفًا.',
            'name.unique' => 'يوجد بالفعل مورد بهذا الاسم لديك.',

            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',

            'address.string' => 'العنوان يجب أن يكون نصًا.',
            'address.max' => 'العنوان لا يمكن أن يتجاوز 255 حرفًا.',
        ];
    }
}
<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreSupplierRequest extends BaseApiRequest
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
                Rule::unique('suppliers')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'phone' => ['nullable', 'regex:/^01[0-9]{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            // 'total_dues' => ['nullable', 'numeric', 'min:0'],
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

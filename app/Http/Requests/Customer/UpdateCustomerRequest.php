<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends BaseApiRequest
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
            //
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('customers')
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
            //
            'name.required' => 'اسم العميل مطلوب.',
            'name.string' => 'اسم العميل يجب أن يكون نصًا.',
            'name.max' => 'اسم العميل لا يمكن أن يتجاوز 255 حرفًا.',
            'name.unique' => 'يوجد بالفعل عميل بهذا الاسم لديك.',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 01 ويتكون من 11 رقمًا.',
            'address.string' => 'العنوان يجب أن يكون نصًا.',
            'address.max' => 'العنوان لا يمكن أن يتجاوز 255 حرفًا.',

        ];
    }
}

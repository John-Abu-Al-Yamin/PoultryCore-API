<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class RegisterRequest extends BaseApiRequest
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
            'name' => 'required|string|min:3|max:255',
            'phone' => ['required', 'string', 'unique:users,phone', 'regex:/^01[0125][0-9]{8}$/'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'has_completed_setup' => 'sometimes|boolean',
            'role' => 'sometimes|string|in:admin,user',
        ];
    }

    public function messages(): array
    {
        return [

            'name.required' => 'الاسم مطلوب.',

            'name.min' => 'الاسم يجب أن يكون على الأقل 3 أحرف.',

            'phone.required' => 'رقم الهاتف مطلوب.',

            'phone.unique' => 'رقم الهاتف مستخدم بالفعل.',

            'phone.regex' => 'رقم الهاتف يجب أن يكون رقم هاتف مصري صحيح.',

            'password.required' => 'كلمة المرور مطلوبة.',

            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 6 أحرف.',

            'password.confirmed' => 'تأكيد كلمة المرور لا يتطابق.',

            'role.in' => 'الدور يجب أن يكون إما admin أو user.',
        ];
    }
}

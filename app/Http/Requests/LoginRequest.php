<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends BaseApiRequest
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
            'phone' => 'required|string|max:255',
            'password' => 'required|string|min:8'
        ];
    }

    public function messages(): array
    {
        return [

            'phone.required' =>
            'رقم الهاتف مطلوب.',
            'phone.exists' =>
            'رقم الهاتف غير موجود.',

            'password.required' =>
            'كلمة المرور مطلوبة.',
            'password.min' =>
            'كلمة المرور يجب أن تكون على الأقل 8 أحرف.',

        ];
    }
}

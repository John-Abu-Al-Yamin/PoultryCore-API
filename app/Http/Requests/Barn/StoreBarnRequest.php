<?php

namespace App\Http\Requests\Barn;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Support\Facades\Auth;
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
                    return $query->where('user_id', Auth::id());
                }),
            ],

            'location' => ['nullable', 'string', 'max:255'],

            'capacity' => ['required', 'nullable', 'integer', 'min:0'],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العنبر مطلوب.',
            'name.string' => 'اسم العنبر يجب أن يكون نصًا.',
            'name.max' => 'اسم العنبر لا يمكن أن يتجاوز 255 حرفًا.',
            'name.unique' => 'يوجد بالفعل عنبر بهذا الاسم لديك.',

            'location.string' => 'موقع العنبر يجب أن يكون نصًا.',
            'location.max' => 'موقع العنبر لا يمكن أن يتجاوز 255 حرفًا.',

            'capacity.required' => 'سعة العنبر مطلوبة.',
            'capacity.integer' => 'سعة العنبر يجب أن تكون عددًا صحيحًا.',
            'capacity.min' => 'سعة العنبر لا يمكن أن تكون أقل من 0.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}

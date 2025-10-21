<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAddressRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|required|string|max:50',
            'phone_number' => 'sometimes|required|string|max:11',
            'address_line' => 'sometimes|required|string|max:350',
            'city' => 'sometimes|required|string',
            'state' => 'sometimes|required|string',
            'neighbourhood' => 'sometimes|required|string',
            'postal_code' => 'nullable|string',
            'is_default' => 'boolean|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'İsim kısmı zorunludur.',
            'full_name.string' => 'İsim kısmı metin olmalıdır.',
            'full_name.max' => 'İsim kısmı en fazla 50 karakter olabilir.',

            'phone_number.required' => 'Telefon numarası zorunludur.',
            'phone_number.string' => 'Telefon numarası metin olmalıdır.',
            'phone_number.max' => 'Telefon numarası en fazla 11 karakter olabilir.',

            'address_line.required' => 'Adres açıklaması zorunludur.',
            'address_line.string' => 'Adres açıklaması metin olmalıdır.',
            'address_line.max' => 'Adres açıklaması en fazla 350 karakter olabilir.',

            'city.required' => 'Şehir kısmı zorunludur.',
            'city.string' => 'Şehir kısmı metin olmalıdır.',

            'state.required' => 'İl/eyalet kısmı zorunludur.',
            'state.string' => 'İl/eyalet kısmı metin olmalıdır.',

            'neighbourhood.required' => 'Mahalle kısmı zorunludur.',
            'neighbourhood.string' => 'Mahalle kısmı metin olmalıdır.',

            'postal_code.string' => 'Posta kodu metin olmalıdır.',

            'is_default.boolean' => 'Varsayılan adres bilgisi geçersizdir.',
        ];
    }
}

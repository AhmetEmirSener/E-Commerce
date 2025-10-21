<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAddressRequest extends FormRequest
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
            'full_name'=>'required|string|max:50',
            'phone_number'=>'required|string|max:11',
            'address_line'=>'required|string|max:350',
            'city'=>'required|string',
            'state'=>'required|string',
            'neighbourhood'=>'required|string',
            'postal_code'=>'nullable|string',
            'is_default'=>'boolean|nullable'

        ];
    }

    public function messages(){
        return [
            'full_name.required' => 'İsim kısmı zorunludur.',
            'full_name.string' => 'İsim kısmı metin olmalıdır.',
            'full_name.max' => 'İsim kısmı en fazla 50 karakter olabilir.',
    
            'phone_number.required' => 'Telefon numarası zorunludur.',
            'phone_number.max' => 'Telefon numarası en fazla 11 karakter olabilir.',
    
            'address_line.required' => 'Adres açıklaması zorunludur.',
            'address_line.string' => 'Adres açıklaması metin olmalıdır.',
            'address_line.max' => 'Adres açıklaması en fazla 350 karakter olabilir.',
    
            'city.required' => 'Şehir kısmı zorunludur.',
            'city.string' => 'Şehir kısmı metin olmalıdır.',
    
            'state.required' => 'İlçe kısmı zorunludur.',
            'state.string' => 'İlçe kısmı metin olmalıdır.',
    
            'neighbourhood.required' => 'Mahalle kısmı zorunludur.',
            'neighbourhood.string' => 'Mahalle kısmı metin olmalıdır.',
    
            'postal_code.string' => 'Posta kodu metin olmalıdır.',
    
            'is_default.boolean' => 'Varsayılan adres bilgisi geçersizdir.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'token'=>'required |string',
            'name'=>'required|max:25|string',
            'surname'=>'required|max:30|string',
            'phone_number' => 'nullable|unique:users,phone_number',
            'phone_number' => 'nullable|regex:/^05[0-9]{9}$/|unique:users,phone_number',

            //'email'=>'required|email|unique:users,email',
            'password'=>'required|string',

        ];
    }
    public function messages():array{
        return[
            'name.required'=>'İsim alanı zorunludur',
            'name.max'=>'İsim alanı en fazla 25 karakter olabilir',
            'name.string'=>'İsim alanı metin olmalıdır',
            'surname.required'=>'Soyisim alanı zorunludur',
            'surname.max'=>'Soyisim alanı en fazla 30 karakter olabilir.',
            'surname.string'=>'Soyisim alanı metin olmalıdır.',
            'phone_number.unique'=>'Telefon numarası zaten kayıtlı!',
            'phone_number.regex' => 'Telefon numarası geçerli değil.',

            /*
            'email.required'=>'Email alanı zorunludur',
            'email.email'=>'Email alanı e-posta kurallarına uymalıdır.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            */
            'password.required'=>'Şifre alanı zorunludur.',
            'token.required'=>'E-mail doğrulaması tamamlanmamış.'
        ];
    }
    protected function prepareForValidation() {
        $phone = $this->phone_number;
        
        if ($phone) {
            $phone = preg_replace('/\D/', '', $phone); // sadece rakam bırak
            
            if (str_starts_with($phone, '90')) {
                $phone = '0' . substr($phone, 2); // 905301234567 → 05301234567
            } elseif (str_starts_with($phone, '5')) {
                $phone = '0' . $phone; // 5301234567 → 05301234567
            }
            
            $this->merge(['phone_number' => $phone]);
        }
    }
}

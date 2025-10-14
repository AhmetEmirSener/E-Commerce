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
            'name'=>'required|max:25|string',
            'surname'=>'required|max:30|string',
            'phone_number' => 'unique:users,phone_number',
            'email'=>'required|email|unique:users,email',
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
            'email.required'=>'Email alanı zorunludur',
            'email.email'=>'Email alanı e-posta kurallarına uymalıdır.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',

            'password.required'=>'Şifre alanı zorunludur',
        ];
    }
}

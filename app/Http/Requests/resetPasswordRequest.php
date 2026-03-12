<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class resetPasswordRequest extends FormRequest
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
            'passwordFirst'=>'string | required',
            'passwordSecond'=>'string | required',
            'token'=>'string | required'
        ];
    }
    public function messages(){
        return[
            'passwordFirst.string'=>'Şifre metin olmalı.',
            'passwordFirst.required'=>'Şifre zorunlu.',

            'passwordSecond.string'=>'Şifre metin olmalı.',
            'passwordFirst.required'=>'Şifre tekrarı zorunlu.',
            'token.required'=>'Oturuma ulaşılamadı.'


        ];
    }
}

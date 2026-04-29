<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserChangePasswordRequest extends FormRequest
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
            'password_old'=>'required | min:3 |string',
            'password_new'=>'required | min:8 |string',
        ];
    }

    public function messages(){
        return[
            'password_old.required'=>'Mevcut şifre alanı zorunlu.',
            'password_old.min'=>'Mevcut şifre en az 3 karakter olmalı.',
            'password_old.string'=>'Mevcut şifre alanı metin olmalı.',
            
            'password_new.required'=>'Yeni şifre alanı zorunlu.',
            'password_new.min'=>'Yeni şifre en az 8 karakter olmalı.',
            'password_new.string'=>'Yeni şifre alanı metin olmalı.',
            
        ];
    }
}

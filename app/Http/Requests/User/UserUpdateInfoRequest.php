<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateInfoRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

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
            'name' => 'nullable|string|min:2|required_without:surname',
            'surname' => 'nullable|string|min:2|required_without:name',
        ];
    }
    public function messages(){
        return[
            'name.required_without' => 'En az ad veya soyad alanlarından biri doldurulmalıdır.',
            'surname.required_without' => 'En az ad veya soyad alanlarından biri doldurulmalıdır.',
    
            'name.string' => 'Adınızı metin olarak giriniz.',
            'surname.string' => 'Soyadınızı metin olarak giriniz.',

            'surname.min' => 'Soyad bölümü en az 2 harf olmalı.',
            'surname.min' => 'Ad bölümü en az 2 harf olmalı.',


        ];
    }
}

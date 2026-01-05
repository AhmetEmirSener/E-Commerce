<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSlider extends FormRequest
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
            'type'=>'required | string',
            'page'=>'required | string',
            'sort'=>'required | integer',
            'title'=>'required | string',
            'is_active'=>'boolean'

        ];
    }
    public function messages(): array{
        return[
            'type.required'=>'Slider tipi zorunlu.',
            'type.string'=>'Slider tipi metin olmalı.',
            'page.required'=>'Sayfa alanı zorunlu.',
            'page.string'=>'Sayfa alanı metin olmalı.',
            'sort.required'=>'Sıralama alanı zorunlu.',
            'sort.integer'=>'Sıralama alanı sayılardan oluşmalı.',
            'title.required'=>'Başlık alanı zorunlu.',
            'title.string'=>'Başlık alanı metin olmalı.',
            'is_active'=>'Durum alanı pasif veya aktif olmalı'

        ];
    }
}

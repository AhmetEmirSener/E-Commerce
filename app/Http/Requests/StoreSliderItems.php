<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSliderItems extends FormRequest
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
            'slider_id'=>'required | exists:sliders,id',
            'ref_type'=>'required|string',
            'ref_id'=>'required|integer',
            'image'=>'nullable | string',
            'mobile_image'=>'nullable | string',
            'link'=>'nullable | string',
            'sort'=>'integer',
            'is_active'=>'boolean',

        ];

    }

    public function messages(): array{
        return[
            'slider_id.required'=>'Slider seçimi zorunlu.',
            'slider_id.exists'=>'Seçtiğiniz slider mevcut değil.',
            'ref_type.required'=>'Referans tipi zorunlu.',
            'ref_type.string'=>'Referans tipi metin olmalı.',
            'ref_id.required'=>'Referans ürünü zorunlu.',
            'ref_id.integer'=>'Referans ürünü sayılardan oluşmalı',
            'image.string'=>'Resim yolu metin olmalı.',
            'mobile_image.string'=>'Mobil resim yolu metin olmalı.',
            'link.string'=>'Link metinden oluşmalı',
            'sort.integer'=>'Sıra Numarası sayıdan oluşmalı',
        ];
    }
}

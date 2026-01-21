<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
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
            'name'=>'required|string|max:50',
            'parent_id'=>'nullable|integer | exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ];

    }

    public function messages():array{
        return[
            'name.required'=>'Kategori adı alanı zorunlu.',
            'name.string'=>'Kategori adı metin olmalı',
            'name.max'=>'Kategori adı en fazla 50 karakter olabilir',

            'parent_id.exists'=>'Ana kategori bulunamadı',

            'image.image' => 'Yüklenen dosya resim olmalıdır.',
            'image.mimes'=>'Resim alanı geçerli formatta olmalı(jpg,jpeg,png,webp)',


        ];
    }
}

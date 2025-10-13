<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertRequest extends FormRequest
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
    {//,exists:products,id
        return [
            'id'=>'exists:adverts,id',
            'product_id'=>'required',
            'title'=>'required|string|max:150',
            'description'=>'nullable|string',
            'avg_rating'=>'nullable',
            'total_comments'=>'nullable',
            'images'=>'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'price'=>'numeric|min:0',
            'expires_at'=>'nullable',
            'status'=>'in:aktif,pasif,beklemede',
            'is_featured'=>'nullable|boolean'
        ];
    }

    public function messages():array{
        return[
            'product_id.required'=>'Ürün seçilmelidir.',
            'title.required'=>'İlan başlığı zorunlu.',
            'title.max'=>'İlan başlığı en fazla 150 karakter olabilir.',
            'description.string' => 'Açıklama yalnızca metin içerebilir.',
            'price.numeric' => 'Fiyat sayısal bir değer olmalıdır.',
            'images.*.image' => 'Her görsel geçerli bir resim olmalıdır.',
            'images.*.mimes' => 'Sadece jpg, jpeg, png veya webp formatı desteklenir.',




        ];
    }
}

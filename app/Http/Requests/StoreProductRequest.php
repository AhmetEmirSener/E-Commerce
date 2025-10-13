<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'id'=>'exists:products,id',
            'name'=>'required|max:75',
            'category_id'=>'required|exists:categories,id',
            'brand_id'=>'nullable|exists:brands,id',
            'price'=>'required|numeric|min:0',
            'stock'=>'required|numeric|min:0',
            'discount_price'=>'nullable|numeric|min:0|lt:price',
            'discount_stock'=>'nullable|numeric|lte:stock',
            'is_discount_active'=>'nullable|boolean',
            'image'=>'nullable|array',
            'status'=>'in:aktif,pasif,beklemede',
            'weight'=>'nullable|numeric'
            
        ];
    }

    public function messages(): array{
        return[
            'name.required'=>'Ürün adı zorunludur',
            'name.max'=>'Ürün adı en fazla 75 karakter olabilir.',
            'category_id.required'=>'Kategori alanı zorunludur.',
            'category_id.exists'=>'Seçilen kategori geçerli değil.',

            'brand_id.exists'=>'Seçilen marka geçerli değil.',

            'price.required'=>'Fiyat alanı zorunludur.',
            'price.numeric'=>'Fiyat alanı sayılardan oluşmalıdır.',
            'stock.required'=>'Stok alanı zorunludur',
            'discount_price.lt' => 'İndirimli fiyat, normal fiyattan düşük olmalıdır.',
            'discount_price.numeric' => 'İndirimli stok sayısal bir değer olmalıdır.',

            'discount_stock.lte'=>'İndirimli stok, normal stoktan fazla olamaz.',
            'discount_stock.numeric' => 'İndirimli stok sayısal bir değer olmalıdır.',

            'weight.numeric'=>'Ağırlık sayısal değer olmalıdır.',
            
            'price.min'=>'Fiyat 0 dan küçük olamaz.'
        ];
    }
}

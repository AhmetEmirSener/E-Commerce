<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name'=>'nullable|max:75',
            'category_id'=>'nullable|exists:categories,id',
            'brand_id'=>'nullable|exists:brands,id',
            'price'=>'nullable|numeric|min:0',
            'stock'=>'nullable|numeric|min:0',
            'status'=>'nullable | in:aktif,pasif,beklemede',
            'weight'=>'nullable|numeric',
            'image' => 'nullable|array',
            'image.*' => 'image|mimes:jpg,jpeg,png,webp',
            'features'=>'nullable|json'
        ];
    }
}

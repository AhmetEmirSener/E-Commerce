<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
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
            'name'=>'required|unique:brands,name',
            'image'=>'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'description'=>'nullable|string|max:400',
            'status'=>'nullable',
        ];
    }

    public function messages(){
        return[
            'name.required'=>'Marka ismi zorunludur.',
            'name.unique'=>'Bu marka zaten kayıtlı.',
            'description.string'=>'Açıklama metin olmalıdır.',
            'description.max'=>'Açıklama en fazla 400 harf olabilir',

        ];
    }
}

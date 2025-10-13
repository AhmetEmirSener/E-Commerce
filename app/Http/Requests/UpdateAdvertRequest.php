<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvertRequest extends FormRequest
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
            'title' => 'sometimes|string|max:150',  
            'description'=>'nullable|string',
            'images'=>'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'expires_at'=>'nullable',
            'status' => 'sometimes|in:aktif,pasif,beklemede',
            'is_featured'=>'nullable|boolean'
        ];
    }
    public function messages():array{
        return [
            'status.in'=>'İlan durumu belirli durumlar yapılabilir.',
            'images.*.image' => 'Her görsel geçerli bir resim olmalıdır.',
            'images.*.mimes' => 'Sadece jpg, jpeg, png veya webp formatı desteklenir.',
            'title.max'=>'İlan başlığı en fazla 150 karakter olabilir.',
            'description.string' => 'Açıklama yalnızca metin içerebilir.',
        ];
    }
}

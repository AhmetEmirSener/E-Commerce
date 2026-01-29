<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
            'rating'=>'required | integer |min:1|max:5',
            'advert_id'=>'required | exists:adverts,id',
            'comment'=>'nullable | string| max:750',
            'image'=>'nullable | json',

        ];
    }
    public function messages():array{
        return[
            'rating.required'=>'Değerlendirme puanı veriniz.',
            'rating.integer'=>'Değerlendirme puan türü yanlış.',
            'rating.min'=>'Değerlendirme puanı en az 1 olabilir.',
            'rating.max'=>'Değerlendirme puanı en fazla 5 olabilir.',
            
            'advert_id.required'=>'Ürün bulunamadı.',
            'advert_id.exists'=>'Ürnü kayıtlarda yok.',
            'comment.string'=>'Yorum içeriği metin olmalı.',
            'comment.max'=>'Yorum içeriği en fazla 750 harf olabilir.'
        ];
    }
}

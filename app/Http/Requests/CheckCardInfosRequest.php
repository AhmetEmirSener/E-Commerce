<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckCardInfosRequest extends FormRequest
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
            // Visa, Master, Amex vs

            'card_number' => ['required', 'digits_between:13,19'],

            // 01 - 12
            'expire_month' => ['required', 'digits:2', 'between:1,12'],

            // 2026
            'expire_year' => ['required', 'digits:2,4'],

            // 3 veya 4
            'cvc' => ['required', 'digits_between:3,4'],

            'card_holder_name' => ['required', 'string', 'max:255'],


            'save_card' => ['nullable', 'boolean'],
        ];

    }
    public function messages(){
        return[
           'card_holder_name.required' => 'Kart sahibi adı zorunludur.',
            'card_holder_name.string' => 'Kart sahibi adı metin olmalıdır.',

            'card_number.required' => 'Kart numarası zorunludur.',
            'card_number.digits_between' => 'Kart numarası geçerli değil.',

            'expire_month.required' => 'Son kullanma ayı zorunludur.',
            'expire_month.digits' => 'Ay 2 haneli olmalıdır.',
            'expire_month.between' => 'Ay 01 ile 12 arasında olmalıdır.',

            'expire_year.required' => 'Son kullanma yılı zorunludur.',
            'expire_year.digits' => 'Yıl 4 haneli olmalıdır.',

            'cvc.required' => 'CVC zorunludur.',
            'cvc.digits_between' => 'CVC 3 veya 4 haneli olmalıdır.',

            'save_card.boolean' => 'Kart kayıt alanı true/false olmalıdır.',


        ];
    }
}

<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CheckTokenPaymentRequest extends FormRequest
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
            'saved_card_id'=>'required | integer ',
            'installment'=>'nullable | digits_between:1,12'

        ];
    }

    public function messages(){
        return[
            'saved_card_id.required'=>'Kayıtlı kart alanı yok!',
            'saved_card_id.integer'=>'Kayıtlı kart formatı geçerli değil'
        ];
    }
}

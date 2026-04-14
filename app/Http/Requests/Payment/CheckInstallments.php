<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CheckInstallments extends FormRequest
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
            'card_number'=>'required | digits:6'
        ];
    }
    public function messages(){
        return[
            'card_number.required'=>'Kart numarasının ilk 6 hanesi zorunlu.',
            'card_number.digits'=>'Kart numarasının 6 hanesi'
        ];
    }
}

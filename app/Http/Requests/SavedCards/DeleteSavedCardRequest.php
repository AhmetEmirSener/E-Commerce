<?php

namespace App\Http\Requests\SavedCards;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSavedCardRequest extends FormRequest
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
            'saved_card_id'=>'required | integer'
        ];
    }
    public function messages(){
        return[
            'saved_card_id.required'=>'Kayıtlı kart alanı yok!',
            'saved_card_id.integer'=>'Kayıtlı kart formatı geçerli değil'
        ];
    }
}

<?php

namespace App\Http\Requests\Cargo;

use Illuminate\Foundation\Http\FormRequest;

class CreateCargoRequest extends FormRequest
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
            'order_id'=>'required | exists:orders,id',
            'all_order_items' => 'boolean',

            'order_items' => 'required_unless:all_order_items,true,1|array',
            
            // 'order_items.*' => 'exists:order_items,id', 

            'cargo_company'     => 'required|string|max:255', 
            'tracking_code'     => 'required|string|unique:order_cargo_details,tracking_code',
            
        ];
    }
}

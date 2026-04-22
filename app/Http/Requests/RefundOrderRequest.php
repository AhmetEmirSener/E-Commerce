<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
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
            'order_items' => 'required|array|min:1',
            'reason' => 'required|string|max:500',

            'order_items.*.item_id' => 'required|exists:order_items,id',
            'order_items.*.quantity' => 'required|integer|min:1',

            'all_order'=>'nullable'
        ];
    }
}

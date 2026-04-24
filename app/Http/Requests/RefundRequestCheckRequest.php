<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequestCheckRequest extends FormRequest
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
            'refund_request_id'=>'required | exists:refund_requests,id',

            'refund_items' => 'required|array|min:1',
            
            'refund_items.*.id' => 'required|exists:refund_request_items,id',
            'refund_items.*.quantity' => 'required|integer|min:1',
            'refund_items.*.status' => 'required| string ',

        ];
    }
}

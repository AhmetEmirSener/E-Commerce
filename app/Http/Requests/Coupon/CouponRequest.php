<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coupon_code'=>'required | string | max:20'
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_code.required'=>'Kupon kodu giriniz.',
            'coupon_code.string'=>'Kupon kodu metin olmalı.',
            'coupon_code.max'=>'Kupon kodu en fazla 20 karakter olabilir.',
        ];
    }
}

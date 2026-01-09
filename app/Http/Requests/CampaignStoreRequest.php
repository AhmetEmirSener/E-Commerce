<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignStoreRequest extends FormRequest
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
            'title'=>'required | string',
            'description'=>'string | nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'mobile_image' => 'nullable| string',
            'link'=>'string | nullable',
            'start_date'=>'date | nullable',
            'end_date'=>'date | nullable'
        ];
    }
}

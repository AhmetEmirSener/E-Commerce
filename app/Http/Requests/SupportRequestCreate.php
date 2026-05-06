<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TurnstileRule;

class SupportRequestCreate extends FormRequest
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
            'user_id'            => 'nullable|exists:users,id',
            'order_id'           => 'nullable|exists:orders,id',
            'contact_name'       => 'required_without:user_id|nullable|string',
            'contact_email'      => 'required_without:user_id|nullable|email',
            'contact_phone'      => 'nullable|string|required_if:contact_preference,phone',
            'topic'              => 'required|string|max:100',
            'message'            => 'required|string|max:500|min:10',
            'contact_preference' => 'required|string|in:email,phone',
            'cf_turnstile_response'=>['required','string', new TurnstileRule()]
        ];
    }

    public function messages(): array
{
    return [
        'user_id.exists'             => 'Belirtilen kullanıcı sistemde kayıtlı değil.',
        'order_id.exists'            => 'Girdiğiniz sipariş numarası geçerli bir siparişe ait değil.',
        
        'contact_name.required_without' => 'Üye girişi yapmadığınız için lütfen adınızı ve soyadınızı belirtin.',
        'contact_email.required_without' => 'Üye girişi yapmadığınız için lütfen e-posta adresinizi belirtin.',
        'contact_email.email'           => 'Lütfen geçerli bir e-posta adresi giriniz.',
        
        'contact_phone.required_if'     => 'İletişim tercihi olarak telefonu seçtiğiniz için telefon numarası girmeniz zorunludur.',
        
        'topic.required'             => 'Lütfen bir destek konusu seçin veya girin.',
        'topic.max'                  => 'Konu başlığı en fazla 100 karakter olabilir.',
        
        'message.required'           => 'Lütfen bize iletmek istediğiniz mesajı yazın.',
        'message.min'                => 'Mesajınız kendinizi açıklayabilmeniz için en az 10 karakter olmalıdır.',
        'message.max'                => 'Mesajınız çok uzun, lütfen 300 karakteri geçmeyecek şekilde özetleyin.',
        
        'contact_preference.required' => 'Size nasıl geri dönüş yapmamızı istediğinizi seçmelisiniz.',
        'contact_preference.in'       => 'İletişim tercihi sadece E-posta veya Telefon olabilir.',
    ];
}
}

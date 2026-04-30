<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp, public string $type){}

    public function envelope(): Envelope
    {
        $subjects = [
            'register'       => 'Kayıt Doğrulama Kodunuz',
            'password-reset' => 'Şifre Sıfırlama Kodunuz',
            'email-change'   => 'Mail Değiştirme Kodunuz',
        ];

        return new Envelope(
            subject: $subjects[$this->type]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp'   => $this->otp,
                'title' => $this->type
            ]
        );
    }
  
}

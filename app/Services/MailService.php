<?php

namespace App\Services;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendOtp(string $email,string $otp,string $type){
        Mail::to($email)->queue(new OtpMail($otp,$type));
    }
}

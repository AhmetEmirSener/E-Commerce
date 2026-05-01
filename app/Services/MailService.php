<?php

namespace App\Services;
use App\Mail\OtpMail;
use App\Mail\OrderPlacedMail;
use App\Models\Order;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendOtp(string $email,string $otp,string $type){
        Mail::to($email)->queue(new OtpMail($otp,$type));
    }

    public function sendOrderPlace(string $email,Order $order){
        Mail::to($email)->queue(new OrderPlacedMail($order));
    }
}

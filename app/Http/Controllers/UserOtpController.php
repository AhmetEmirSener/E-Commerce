<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class UserOtpController extends Controller
{

    public function sendOtp(Request $request){
        $phone = $request->phone_number;
        
        $rateKey = 'otp_rate'.$phone;
        if(Cache::has($rateKey)){
            return response()->json(['error'=>'Çok sık istek. 1 dakika bekleyin.'], 429);
        }
        Cache::put($rateKey,true,now()->addMinute());

        $code = random_int(100000,999999);
        $token = (string) Str::uuid();

        $cacheKey = 'phone_verif'.$token;
        Cache::put($cacheKey,[
            'phone'=>$phone,
            'code_hash'=>Hash::make((string)$code),
            'attempts'=>0
        ], now()->addMinutes(5));

        Log::info("OTP for {$phone} is {$code}");

        return response()->json(['message'=>'Doğrulama kodu gönderildi.','verification_code'=>$token],200);
    }


    public function verifyOtp(Request $request,$token){
        $code = $request->code;

        $cacheKey= 'phone_verif'.$token;
        if(!Cache::has($cacheKey)){
            return response()->json(['error'=>'Kod geçersiz veya süresi dolmuş.'], 400);
        }
        $data = Cache::get($cacheKey);
        if($data['attempts']>=5){
            Cache::forget($cacheKey);
            return response()->json(['error'=>'Çok fazla yanlış deneme. Tekrar istekte bulunun.'],429);
        }

        if(!Hash::check((string)$code, $data['code_hash'])){
            $data['attempts']++;
            Cache::put($cacheKey, $data, now()->addMinutes(5));
            return response()->json(['error'=>'Kod yanlış.'], 400);
        }
        Cache::forget($cacheKey);

        $verifiedToken=(string) Str::uuid();
        Cache::put('phone_verified:'.$verifiedToken,[
            'phone'=>$data['phone']
        ],now()->addMinutes(10));

        return response()->json([
            'message'=>'Telefon numarası doğrulandı',
            'phone_verified_token'=>$verifiedToken,
        ],200);
    }

}

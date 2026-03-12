<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Http\Requests\RegisterSendOtpRequest;

use Illuminate\Http\Request;

class UserOtpController extends Controller
{

    public function sendOtp(RegisterSendOtpRequest $request){
        $data = $request->validated();
        if(!$data['email']){
            return response()->json(['message'=>'Email bulunamadı.'],404);
        }

        $email = $data['email'];

    
        $isRegisted= User::where('email',$email)->count();
        if($isRegisted){
            return response()->json(['message'=>'Email zaten kayıtlı.'],400);
        }
        
        $rateKey = 'otp_rate'.$email;
        if(Cache::has($rateKey)){
            return response()->json(['message'=>'Çok sık istek. 1 dakika bekleyin.'], 429);
        }
        Cache::put($rateKey,true,now()->addMinute());

        $code = random_int(100000,999999);
        $token = (string) Str::uuid();

        $cacheKey = 'email_verif'.$token;
        Cache::put($cacheKey,[
            'email'=>$email,
            'code_hash'=>Hash::make((string)$code),
            'attempts'=>0
        ], now()->addMinutes(5));

        Log::info("OTP for {$email} is {$code}");

        return response()->json(['message'=>'Doğrulama kodu gönderildi.','token'=>$token],200);
    }


    public function verifyOtp(Request $request){
        $code = $request->code;
        $token = $request->token;

        $cacheKey= 'email_verif'.$token;
        if(!Cache::has($cacheKey)){
            return response()->json(['message'=>'Oturum geçersiz veya süresi dolmuş.'], 400);
        }
        $data = Cache::get($cacheKey);
        if($data['attempts']>=5){
            Cache::forget($cacheKey);
            return response()->json(['message'=>'Çok fazla yanlış deneme. Tekrar istekte bulunun.'],429);
        }

        if(!Hash::check((string)$code, $data['code_hash'])){
            $data['attempts']++;
            Cache::put($cacheKey, $data, now()->addMinutes(5));
            return response()->json(['message'=>'Doğrulama kodu yanlış.'], 400);
        }
        Cache::forget($cacheKey);

        $verifiedToken=(string) Str::uuid();
        Cache::put('email_verified:'.$verifiedToken,[
            'email'=>$data['email']
        ],now()->addMinutes(10));

        return response()->json([
            'message'=>'E-mail adresi doğrulandı.',
            'token'=>$verifiedToken,
        ],200);
    }

}

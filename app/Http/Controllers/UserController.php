<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;




class UserController extends Controller
{
    public function register(RegisterRequest $request){
        try {
            $userData = $request->validated();
            $token = $userData['token'];

            $cacheKey = 'phone_verified:'.$token;
            if(!Cache::has($cacheKey)){
                return response()->json(['error'=>'Telefon doğrulaması bulunamadı!'],400);
            }

            $phone=Cache::get($cacheKey)['phone'];
            Cache::forget($cacheKey);

            $userData['phone_number']=$phone;
            $hashed = Hash::make($userData['password']);
            $userData['password']=$hashed;
            $userData['phone_number_verified_at'] = now();
            unset($userData['token']);
            $user= User::create($userData);
            return response()->json(['message'=>'Kayıt başarılı!'],201);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function login(Request $request ){
        try {
            $credentials = $request->validate([
                'login' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            [
                'login.required' => 'E-posta veya telefon numarası alanı zorunludur.',
                'login.string'   => 'E-posta veya telefon numarası metin olmalıdır.',
                'password.required' => 'Şifre alanı zorunludur.',
                'password.string'   => 'Şifre yalnızca metin olmalıdır.',
            ]);

            $loginIpnut = $credentials['login'];
            $loginPassword = $credentials['password'];
            $field = filter_var($loginIpnut,FILTER_VALIDATE_EMAIL) ? 'email':'phone_number';

            $user = User::where($field,$loginIpnut)->first();
            
            if(!$user || !Hash::check($loginPassword,$user->password)){
                return response()->json(['message'=>'Geçersiz giriş bilgileri'],401);
            }
            
            $token = $user->createToken('access_token')->plainTextToken;
 

            $cookie = cookie(
                'access_token',       // cookie adı
                $token,         // değer
                14400,                   // dakika cinsinden geçerlilik süresi (örnek: 60 dk)
                null,                 // path (isteğe bağlı)
                null,                 // domain
                false,                 // secure http/https
                true,                 // httpOnly — JS erişemez
                false,                // raw
                'Strict'              // SameSite
            );

            return response()->json([
                'message'=>'Giriş başarılı',
                'user'=>$user,
                'token'=>$token //postman testi icin
            ],200)->withCookie($cookie);

          


        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function logout(Request $request){
        try {
            $user = $request->user();
            if(!$user){
                return response()->json(['message'=>'Oturum doğrulanamadı!'],401);
            }else{                
                $user->currentAccessToken()->delete(); 
            }

            $cookie = cookie('access_token', '', -1, null, null, true, true, false, 'Strict'); 

            return response()->json(['message'=>'Çıkış başarılı.'],200)->withCookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}

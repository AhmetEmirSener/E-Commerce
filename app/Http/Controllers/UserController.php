<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;



class UserController extends Controller
{
    public function register(RegisterRequest $request,$verifiedToken){
        try {
            $cacheKey = 'phone_verified:'.$verifiedToken;
            if(!Cache::has($cacheKey)){
                return response()->json(['error'=>'Telefon doğrulaması bulunamadı!'],400);
            }

            $phone=Cache::get($cacheKey)['phone'];
            Cache::forget($cacheKey);

            $userData = $request->validated();
            $userData['phone_number']=$phone;
            $hashed = Hash::make($userData['password']);
            $userData['password']=$hashed;
            $userData['phone_number_verified_at'] = now();
            $user= User::create($userData);
            return response()->json(['message'=>'Kayıt başarılı!'],201);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}

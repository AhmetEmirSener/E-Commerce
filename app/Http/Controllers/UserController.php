<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\sendResetOtpRequest;
use App\Http\Requests\resetPasswordRequest;

use App\Http\Requests\RefundOrderRequest;

use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;



class UserController extends Controller
{
    public function register(RegisterRequest $request){
        try {
            $userData = $request->validated();
            $token = $userData['token'];

            $cacheKey = 'email_verified:'.$token;
            if(!Cache::has($cacheKey)){
                return response()->json(['message'=>'E-mail doğrulaması bulunamadı!'],400);
            }

            $email=Cache::get($cacheKey)['email'];
            Cache::forget($cacheKey);

            $userData['email']=$email;
            $hashed = Hash::make($userData['password']);
            $userData['password']=$hashed;
            $userData['email_verified_at'] = now();
            unset($userData['token']);
            $user= User::create($userData);
            return response()->json(['message'=>'Kayıt başarılı!'],201);
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }


    public function login(Request $request ){
        try {
            $credentials = $request->validate([
                'email' => ['required', 'string','email'],
                'password' => ['required', 'string'],
            ],
            [
                'email.required' => 'E-posta alanı zorunludur.',
                'email.string'   => 'E-posta metin olmalıdır.',
                'email.email'=>'E-posta geçerli değil.',
                'password.required' => 'Şifre alanı zorunludur.',
                'password.string'   => 'Şifre yalnızca metin olmalıdır.',
            ]);

            $email = $credentials['email'];
            $loginPassword = $credentials['password'];

            $user = User::where('email',$email)->first();
            
            if(!$user || !Hash::check($loginPassword,$user->password)){
                return response()->json(['message'=>'E-mail veya şifre yanlış.'],401);
            }
            

            $accessToken = JWTAuth::fromUser($user);
            $refreshToken = JWTAuth::customClaims([
                'exp'=>now()->addDays(30)->timestamp,
                'type'=>'refresh'
            ])->fromUser($user);

            $accessCookie = cookie(
                'access_token',       // cookie adı
                $accessToken,               // token değeri
                65 ,                    // 1 + jwt tolerans saat geçerli
                '/',                  // path
                null,                 // domain
                false,                // secure
                true,                 // httpOnly
                false,                // raw
                'Lax'              // SameSite
            );

            $refreshCookie = cookie('refresh_token',
             $refreshToken,
                60 * 24 * 30,
                '/',
                null,
                false,
                true,
                false,
                'Lax');


            return response()->json([
                'message'=>'Giriş başarılı',
            ],200)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);

          


        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function logout(Request $request){
        try {
            $accessToken = $request->cookie('access_token');
            $refreshToken = $request->cookie('refresh_token');

            if ($accessToken) JWTAuth::setToken($accessToken)->invalidate();
            if ($refreshToken) JWTAuth::setToken($refreshToken)->invalidate();

        } catch (\Exception $e) {
        }
            return response()->json(['message' => 'Çıkış yapıldı'], 200)
                ->withCookie(cookie()->forget('access_token'))
                ->withCookie(cookie()->forget('refresh_token'));

              
       
    }



    public function resetPasswordOtp(sendResetOtpRequest $request){
        try {

            $validated = $request->validated();

            $email =$validated['email'];

            $cacheLimit = 'password_reset_for_'.$email;
            if(Cache::has($cacheLimit)){
                return response()->json(['message'=>'Şifrenizi yakın zamanda değiştirdiniz'],429);
            }


            $rateKey ='otp_rate'.$email;

            $user = User::where('email',$email)->first();
            
            if(!$user){
                return response()->json(['message'=>'Doğrulama kodu gönderildi.'],200);
            }

            if(Cache::has($rateKey)){
                return response()->json(['message'=>'Çok sık istek. 1 dakika bekleyin.'], 429);
            }

            Cache::put($rateKey,true,now()->addMinute());

            $code = random_int(100000,999999);
            $token = (string) Str::uuid();
            
            $cacheKey = 'reset_password:'.$token;
            Cache::put($cacheKey,[
                'email'=>$email,
                'code_hash'=>Hash::make((string)$code),
                'attempts'=>0
            ], now()->addMinutes(15));

            Log::info("RESET OTP FOR {$email} is {$code}");

            return response()->json(['message'=>'Doğrulama kodu gönderildi.','token'=>$token],200);

        } catch (\Throwable $th) {
            return response()->json(['message'=>'Bir hata oluştu'],500);
        }
    }

    public function verifyPasswordOtp (Request $request){
        try {
            $code = $request->code;
            $token = $request->token;

            $cacheKey = 'reset_password:'.$token;
    
            $data = Cache::get($cacheKey);
            if(!$data){
                return response()->json(['message'=>'Doğrulama kodu yanlış.'], 400);
            }

            if($data['attempts']>=5){
                Cache::forget($cacheKey);
                return response()->json(['message'=>'Çok fazla yanlış deneme. Tekrar istekte bulunun.'],429);
            }
            if(!Hash::check((string)$code,$data['code_hash'])){
                $data['attempts']++;
                Cache::put($cacheKey,$data,now()->addMinute(5));
                return response()->json(['message'=>'Doğrulama kodu yanlış.'], 400);
            }
            Cache::forget($cacheKey);

            $verifiedToken= (string) Str::uuid();
            Cache::put('password_reset_verified:'.$verifiedToken,[
                'email'=>$data['email']
            ],now()->addMinutes(10));
            
            return response()->json([
                'message'=>'E-mail adresi doğrulandı yeni şifreinizi girin.',
                'token'=>$verifiedToken,
            ],200);



        } catch (\Throwable $th) {
            return response()->json(['message'=>'Beklenmedik bir hata oluştu'],500);
        }
    }


    public function resetPassword(resetPasswordRequest $request){
        try {
            $requestData = $request->validated();


            $cacheKey = 'password_reset_verified:'.$requestData['token'];

            $cache = Cache::get($cacheKey);
            if(!$cache){
                return response()->json(['message'=>'E-mail doğrulaması bulunamadı!'],400);
            }
            Cache::forget($cacheKey);
            unset($requestData['token']);

            $email = $cache['email'];

            $user= User::where('email',$cache['email'])->firstOrFail();

            $user->password=Hash::make($requestData['passwordFirst']);
            $user->save();

            $cacheLimit = 'password_reset_for_'.$email;
            Cache::put($cacheLimit,true,now()->addHours(6));

            return response()->json(['message'=>'Şifre sıfırlama başarılı, tekrar giriş yapınız']);
            
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }


    public function refundRequest(RefundOrderRequest $request,$orderId){
        try {
            $data = $request->validated();
            $user = $request->get('auth_user');

            $order = Order::where('id',$orderId)->with('payment','orderItems')->first();

            if($order->user_id !== $user->id){
                return response()->json(['message'=>'Sipariş bulunamadı']);
            };
            if(!in_array($order->status, ['completed'])){
                return response()->json(['message' => 'Sadece teslim edilen ürünler iade edilebilir.'], 400);
            }
            
            /*
            $existingRequest = RefundRequest::where('order_id', $order->id)
            ->whereNotIn('status', ['rejected'])
            ->exists();

            if($existingRequest){
                return response()->json(['message' => 'Bu sipariş için zaten bir iade talebi mevcut.'], 400);
            }
            */
            
            $orderItems=collect();


            foreach($data['order_items'] as $item){
                $orderItem = $order->orderItems->where('id', $item['item_id'])->first();
                if(!$orderItem) continue;

                // Daha önce iade edilmiş miktarı çıkar
                $alreadyRefunded = RefundRequestItem::whereHas('refundRequest', function($q){
                    $q->whereNotIn('status', ['rejected']);
                })->where('order_item_id', $orderItem->id)->sum('quantity');

                $availableQuantity = $orderItem->quantity - $alreadyRefunded;
                if($availableQuantity <= 0) continue;

                $quantity = min($item['quantity'], $availableQuantity);

                $orderItems->push([
                    'order_item_id' => $orderItem->id,
                    'quantity'      => $quantity,
                    'amount'        => $orderItem->price * $quantity,
                ]);
            }

        if($orderItems->isEmpty()){
            return response()->json(['message' => 'İade edilebilir ürün bulunamadı.'], 400);
        }

        DB::transaction(function() use ($order, $user, $data, $orderItems){
            $refundRequest = RefundRequest::create([
                'order_id' => $order->id,
                'user_id'  => $user->id,
                'status'   => 'pending',
                'reason'   => $data['reason'],
            ]);

            foreach($orderItems as $item){
                RefundRequestItem::create([
                    'refund_request_id' => $refundRequest->id,
                    'order_item_id'     => $item['order_item_id'],
                    'quantity'          => $item['quantity'],
                    'amount'            => $item['amount'],
                ]);
            }
        });

        return response()->json(['message' => 'İade talebiniz alındı, inceleme sonrası size bilgi verilecektir.']);


            
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);

        }
    }

}

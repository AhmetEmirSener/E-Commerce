<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Review;

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
use App\Http\Requests\User\UserUpdateInfoRequest;
use App\Http\Requests\User\UserChangePasswordRequest;


use App\Http\Resources\ReviewResource;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use App\Models\Order;
use App\Models\RefreshToken;
use App\Models\SavedCard;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\MailService;


class UserController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService){
        $this->mailService = $mailService;
    }

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
            $userData['kvkk_accepted_at']= now();
            $userData['membership_accepted_at']= now();

            if ($request->marketing_consent) {
                $userData['marketing_consent_at'] = now();
            }

            unset($userData['token']);
            unset($userData['agreements']); 
            unset($userData['marketing_consent']);

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

            RefreshToken::create([
                'user_id'     => $user->id,
                'token_hash'  => hash('sha256', $refreshToken),
                'device_info' => $request->userAgent(),
                'ip_address'  => $request->ip(),
                'expires_at'  => now()->addDays(30),
            ]);

            $accessCookie = cookie(
                'access_token',       // cookie adı
                $accessToken,               // token değeri
                15 ,                    // 1 + jwt tolerans saat geçerli // 65 di 15 e çektim
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

            $isLoggedCookie = cookie('is_logged', Str::random(16), 60 * 24 * 30, '/', null, false, false, false, 'Lax');



            return response()->json([
                'message'=>'Giriş başarılı',
            ],200)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($isLoggedCookie);

          


        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function logout(Request $request){
        try {
            $accessToken = $request->cookie('access_token');
            $refreshToken = $request->cookie('refresh_token');

            if ($accessToken) JWTAuth::setToken($accessToken)->invalidate();
            if ($refreshToken){
                RefreshToken::where('token_hash',hash('sha256',$refreshToken))->delete();
                JWTAuth::setToken($refreshToken)->invalidate();
            }

        } catch (\Exception $e) {
        }
            return response()->json(['message' => 'Çıkış yapıldı'], 200)
                ->withCookie(cookie()->forget('access_token'))
                ->withCookie(cookie()->forget('refresh_token'))
                ->withCookie(cookie()->forget('is_logged'));

              
       
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
            
            $this->mailService->sendOtp($email,$code,'password-reset');

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

            $order = Order::where('id',$orderId)->with('payment','orderItems.cargoItem.OrderCargoDetail','orderItems.product','orderCargoDetails.cargoItems')->first();
            
            if(!$order){
                return response()->json(['message' => 'Sipariş bulunamadı.'], 404);
            }

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

                $deliveredAt = $orderItem->cargoItem?->orderCargoDetail?->delivered_at;
                if (!$deliveredAt) {
                    return response()->json(['message' => 'Ürün henüz teslim edilmemiş.'], 422);
                }
                if (Carbon::parse($deliveredAt)->addDays(14)->isPast()) {
                    return response()->json(['message' => $orderItem->product->name.' ürününün iade süresi dolmuş.'], 422);
                }

                // Daha önce iade edilmiş miktarı çıkar
                $alreadyRefunded = RefundRequestItem::whereHas('refundRequest',
              //   function($q){ $q->whereNotIn('status', ['rejected']);}
                )->where('order_item_id', $orderItem->id)->sum('quantity');

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




    public function usersReview(Request $request){
        try {
            $user = $request->get('auth_user');

            $reviews = Review::where('user_id',$user->id)->where('status','!=','Pasif')->with('advert.product')->get();
            return ReviewResource::collection($reviews);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);

        }
    }

    public function deleteReview(Request $request,$id){
        try {
            $user = $request->get('auth_user');
            $review = Review::where('id',$id)->first();
            if (!$review || $review->user_id !== $user->id || $review->status == 'Pasif'){
                return response()->json(['message'=>'Yorum bulunamadı'],404);
            } 
            
            $review->status = 'Pasif';
            $review->save();

            return response()->json(['message'=>'Yorum silindi'],200);


        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);

        }
    }


    public function update(UserUpdateInfoRequest $request){
        $data = array_filter($request->validated(), fn($v) => !is_null($v));

        $user = $request->get('auth_user');

        $user->update($data);
            
        return response()->json(['message'=>'Güncelleme Başarılı.'],200);
    }



    public function sendOtp(sendResetOtpRequest $request){
        try {
            $data = $request->validated();
            $email =$data['email'];
            $alreadyHave = User::where('email',$email)->first();
            $user = $request->get('auth_user');
            if($alreadyHave){
                return response()->json(['message'=>'Email adresi zaten kullanılıyor.'], 400);
            }

            $cacheLimit = 'new_changed_email: '.$user->email;
            if(Cache::has($cacheLimit)){
                return response()->json(['message'=>'E-posta adresinizi yakın zamanda değiştirdiniz.'],429);
            }
            $rateKey = 'otp_rate:'.$email;
            $userRateKey = 'user_otp_rate:'.$user->id;

            if(Cache::has($rateKey) || Cache::has($userRateKey)){
                return response()->json(['message'=>'Çok sık istek. 5 dakika bekleyin.'], 429);
            }

            Cache::put($rateKey,true,now()->addMinute(5));
            Cache::put($userRateKey,true,now()->addMinute(5));

            $code = random_int(100000,999999);
            $token = (string) Str::uuid();

            $cacheKey = 'changeEmail:'.$token;

            Cache::put($cacheKey,[
                'email'=>$email,
                'code_hash'=>Hash::make((string)$code),
                'user_id'=>$user->id,
                'attempts'=>0
            ], now()->addMinutes(5));

            $this->mailService->sendOtp($email,$code,'email-change');

            Log::info("RESET OTP FOR {$email} is {$code}");

            return response()->json(['message'=>"{$email} adresine doğrulama kodu gönderildi",
            'token'=>$token
        ],200);

            

        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);

        }

    }

    public function confirmAndUpdateEmail(Request $request){
        try {
            $otp = $request->code;
            $token = $request->token;
            $user = $request->get('auth_user');

            $cacheKey = 'changeEmail:'.$token;
            $data = Cache::get($cacheKey);
            if(!$data){
                return response()->json(['message'=>'Doğrulama isteği bulunamadı.'], 400);
            }

            if($user->id !== $data['user_id']){
                return response()->json(['message'=>'Doğrulama isteği gerçekleştirilemedi.'], 400);
            }

            if($data['attempts']>=5){
                Cache::forget($cacheKey);
                return response()->json(['message'=>'Çok fazla yanlış deneme. Tekrar istekte bulunun.'],429);
            }
            if(!Hash::check((string)$otp, $data['code_hash'])){
                $data['attempts']++;
                Cache::put($cacheKey,$data,now()->addMinute(5));
                return response()->json(['message'=>'Doğrulama kodu yanlış.'], 400);
            }

            Cache::forget($cacheKey);
            
            $user->email = $data['email'];
            $user->email_verified_at = now();
            $user->save();

            Cache::put('new_changed_email: '.$data['email'], true, now()->addDays(7));

            return response()->json(['message'=>'Email adresi güncellendi.','new_email'=>$data['email']]);



        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }


    public function changePassword(UserChangePasswordRequest $request){
        $data = $request->validated();
        $user= $request->get('auth_user');

        $userRate = 'user_changed_password: '.$user->id;
        if(Cache::has($userRate)){
            return response()->json(['message'=>'24 saat içerisinde 1 kere şifre değiştirebilirsiniz.'],429);   
        }

        if($data['password_old'] === $data['password_new']){
            return response()->json(['message'=>'Mevcut şifre ile yeni şifre aynı olamaz.'],400);
        }

        $currentPassword =$data['password_old'];

        if(!Hash::check($currentPassword,$user->password)){
            return response()->json(['message'=>'Mevcut şifreniz eksik veya hatalı.'],403);
        }

        try {
          
            $newPassword = Hash::make($data['password_new']);
            $user->password = $newPassword;
            $user->save();

            $currentTokenHash = hash('sha256',$request->cookie('refresh_token'));
            RefreshToken::where('user_id',$user->id)
            ->where('token_hash','!=',$currentTokenHash)->delete();


            $rate = 'user_changed_password: '.$user->id;
            Cache::put($rate,true,now()->addDays(1));
            return response()->json(['message'=>'Şifreniz başarıyla güncellendi'],200);



        } catch (\Throwable $th) {
            return response()->json(['message' => 'Bir hata oluştu, lütfen tekrar deneyin.'], 500);

        }
       
    }


    public function savedCards(Request $request){
        $user = $request->get('auth_user');

        return response()->json([
            'data'=>$user->savedCards
        ]);
    }

    public function updateToDefault(Request $request,$id){
        
        $user = $request->get('auth_user');

        $card = $user->savedCards()->find($id);

        if(!$card){
            return response()->json(['message'=>'Kayıtlı kart bulunamadı.'],404);
        }
        if ($card->is_default) {
            return response()->json([
                'message' => 'Bu kart zaten varsayılan kartınız.', 
            ],400);
        }

        $user->savedCards()->update(['is_default' => 0]);
        $card->update(['is_default' => 1]);

        return response()->json(['message'=>'Varsayılan kart güncellendi.','newDefault'=>$card->id]);

        


    }

    public function cartCount(Request $request){
        $user = $request->get('auth_user');
        $cartCount=0;
        if($user->cartItems){
            $cartCount = $user->allCartItems->sum('quantity');
        }
        return response()->json(['count'=>$cartCount]);
    
    }

}

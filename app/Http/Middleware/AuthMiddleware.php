<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;

use App\Models\RefreshToken;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $token = $request->cookie('access_token');
        $refreshToken = $request->cookie('refresh_token');
        if (!$token && $refreshToken) {
            return $this->handleRefreshToken($request, $next);

        }

        if (!$token) {
            return response()->json(['message' => 'Lütfen giriş yapınız.'], 401);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            $request->merge(['auth_user' => $user]);
            return $next($request);

        } catch (TokenExpiredException $e) {
            return $this->handleRefreshToken($request, $next);

        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token geçersiz.'], 401);

        } catch (JWTException $e) {
            return response()->json(['message' => 'Token hatası.'], 401);
        }
    }

    private function handleRefreshToken($request, $next)
    {
        $refreshToken = $request->cookie('refresh_token');
        if(!$refreshToken){
            return response()->json(['message'=>'Oturum süresi doldu, lütfen giriş yapınız'],401);
        }
        try {
          
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if($payload->get('type') !== 'refresh'){
                return response()->json(['message'=>'Geçersiz token.'],401);
            }

            $tokenHash = hash('sha256',$refreshToken);
            $storedToken = RefreshToken::where('token_hash',$tokenHash)
            ->where('expires_at','>',now())->first();

            if(!$storedToken){
                return response()->json(['message' => 'Oturum geçersiz, lütfen tekrar giriş yapınız.'], 401);
            }


           // JWTAuth::setToken($refreshToken)->checkOrFail();
            $user = JWTAuth::setToken($refreshToken)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Kullanıcı bulunamadı.'], 401);
            }

            $newAccessToken = JWTAuth::fromUser($user);
            $newRefreshToken = JWTAuth::customClaims([
                'exp'=>now()->addDays(30)->timestamp,
                'type'=>'refresh'
            ])->fromUser($user);    

            DB::transaction(function () use ($user, $newRefreshToken, $storedToken, $request) {

                RefreshToken::create([
                    'user_id'     => $user->id,
                    'token_hash'  => hash('sha256', $newRefreshToken),
                    'device_info' => $storedToken->device_info, 
                    'ip_address'  => $request->ip(),
                    'expires_at'  => now()->addDays(30),
                ]);

                $storedToken->delete();
            });
            
            JWTAuth::setToken($refreshToken)->invalidate();

         

            $accessCookie = cookie('access_token', $newAccessToken, 15, '/', null, false, true, false, 'Lax');
            $refreshCookie = cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, '/', null, false, true, false, 'Lax');
            $isLoggedCookie = cookie('is_logged',  Str::random(16), 60 * 24 * 30, '/', null, false, false, false, 'Lax');

            $request->merge(['auth_user' => $user]);

            return $next($request)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($isLoggedCookie);

        } catch (\Throwable $th) {

            return response()->json(['message' => 'Oturum süresi doldu, lütfen giriş yapınızsaas.'], 401);
        }
    }




    
}

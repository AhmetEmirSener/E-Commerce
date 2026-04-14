<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
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

            $user = JWTAuth::setToken($refreshToken)->authenticate();

            JWTAuth::setToken($refreshToken)->invalidate();

            $newAccessToken = JWTAuth::fromUser($user);
            $newRefreshToken = JWTAuth::customClaims([
                'exp'=>now()->addDays(30)->timestamp,
                'type'=>'refresh'
            ])->fromUser($user);

            $accessCookie = cookie('access_token', $newAccessToken, 60, '/', null, false, true, false, 'Lax');
            $refreshCookie = cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, '/', null, false, true, false, 'Lax');

            $request->merge(['auth_user' => $user]);

            return $next($request)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
        } catch (\Throwable $th) {
            dd($th->getMessage());

        return response()->json(['message' => 'Oturum süresi doldu, lütfen giriş yapınızsaas.'], 401);
        }
    }




    
}

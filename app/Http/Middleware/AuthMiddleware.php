<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        try {

            $token = $request->cookie('access_token'); // cookie’den oku
            if (!$token) {
                return response()->json(['message' => 'Lütfen giriş yapınız.'], 401);
            }
            $user = JWTAuth::setToken($token)->authenticate();

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['message' => 'Token geçersiz'], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['message' => 'Token süresi dolmuş'], 401);
            } else {

                $token = $request->cookie('access_token');
                if ($token) {
                    try {
                        $user = JWTAuth::setToken($token)->authenticate();
                    } catch (\Exception $ex) {
                        return response()->json(['message' => 'Token hatası'], 401);
                    }
                } else {
                    return response()->json(['message' => 'Token bulunamadı'], 401);
                }
            }
        }

        $request->merge(['auth_user' => $user]);
        return $next($request);
    }
}

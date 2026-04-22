<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next) 
    {
        try {
            $token  = JWTAuth::getToken(); 
            if(!$token){
                return  response()->json(['error' => 'token not Provided']);
            }
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user || $user->is_login === false) {
                return response()->json([
                    'success' => false,
                    'error' => 'Account deleted or session terminated'
                ], 401);
            }
        } catch (TokenInvalidException) {
            return response()->json(['error' => 'token invalid'], 404);
        } catch (TokenExpiredException) {
            return response()->json(['error' => 'token expired'], 404);
        }
        $request->attributes->add(['user' => $user]);
        return $next($request);
    }
}

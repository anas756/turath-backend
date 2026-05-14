<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class checkAppTokenSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-App-Secret');

        if ($secret !== env('APP_SECRET')) {
            return response()->json([
                'message' => 'Unauthorized Access'
            ], 401);
        }
        return $next($request);
    }
}

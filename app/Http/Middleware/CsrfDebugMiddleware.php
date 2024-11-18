<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CsrfDebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::info('CSRF Debug', [
            'XSRF-TOKEN Cookie' => $request->cookie('XSRF-TOKEN'),
            'X-XSRF-TOKEN Header' => $request->header('X-XSRF-TOKEN'),
        ]);

        return $next($request);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->app['router']->middleware('csrf_debug', function ($request, $next) {
            Log::info('CSRF Debug', [
                'XSRF-TOKEN Cookie' => $request->cookie('XSRF-TOKEN'),
                'X-XSRF-TOKEN Header' => $request->header('X-XSRF-TOKEN'),
            ]);
            return $next($request);
        });
    }
    
}

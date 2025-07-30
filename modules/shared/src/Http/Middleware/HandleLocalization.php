<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the locale from the cookie or use the default locale
        $locale = $request->cookie('locale') ?? config('app.locale');

        // Set the application locale
        App::setLocale($locale);

        View::share('locale', $locale);

        return $next($request);
    }
}

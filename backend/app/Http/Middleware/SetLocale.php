<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Set app locale from:
     *  1. Authenticated user's preferred_locale
     *  2. Accept-Language header
     *  3. Default 'en'
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = 'en';

        if ($request->user()) {
            $locale = $request->user()->preferred_locale ?? 'en';
        } elseif ($request->hasHeader('Accept-Language')) {
            $accepted = substr($request->header('Accept-Language'), 0, 2);
            $locale   = in_array($accepted, ['en', 'bn']) ? $accepted : 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TrackLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $key = 'last_seen_' . Auth::id();

            // scrive su DB al massimo 1 volta al minuto per utente
            if (!Cache::has($key)) {
                Auth::user()
                    ->forceFill(['last_seen_at' => now()])
                    ->saveQuietly();

                Cache::put($key, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}
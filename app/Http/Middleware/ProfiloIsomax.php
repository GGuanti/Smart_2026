<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProfiloIsomax
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('isomax middleware', [
            'user_id' => auth()->id(),
            'profilo' => auth()->user()->profilo ?? null,
            'path' => request()->path(),
          ]);
        if (!auth()->check() || auth()->user()->profilo !== 'Isomax') {
            abort(403);
        }

        return $next($request);
    }
}

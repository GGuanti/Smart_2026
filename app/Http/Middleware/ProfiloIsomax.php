<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProfiloIsomax
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->profilo, ['Isomax', 'admin'], true)) {
            abort(403);
        }

        return $next($request);
    }
}

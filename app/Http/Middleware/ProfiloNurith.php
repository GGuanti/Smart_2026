<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProfiloNurith
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->profilo !== 'Nurith') {
            abort(403);
        }

        return $next($request);
    }
}

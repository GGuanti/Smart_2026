<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProfiloNurith
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->profilo, ['Nurith', 'admin'], true)) {
            abort(403);
        }



        return $next($request);
    }
}

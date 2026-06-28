<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class TrackLastSeen
{
public function handle(Request $request, Closure $next)
{
    if (Auth::check()) {
        $userId = Auth::id();

        // --- last_seen_at (come ora) ---
        $key = 'last_seen_' . $userId;
        if (!Cache::has($key)) {
            Auth::user()->forceFill(['last_seen_at' => now()])->saveQuietly();
            Cache::put($key, true, now()->addMinute());
        }

        // --- storico presenza: slot da 5 minuti ---
        $slot = now()->startOfMinute()->minute(
            intdiv(now()->minute, 5) * 5
        )->second(0);

        $slotKey = 'presence_' . $userId . '_' . $slot->timestamp;
        if (!Cache::has($slotKey)) {
            \DB::table('user_presence')->insertOrIgnore([
                'user_id'    => $userId,
                'slot'       => $slot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::put($slotKey, true, now()->addMinutes(5));
        }
    }

    return $next($request);
}





}

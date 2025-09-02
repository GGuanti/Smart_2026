<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Puoi registrare servizi personalizzati qui, se servono
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fallback per compatibilità con vecchie versioni MySQL (opzionale)
        Schema::defaultStringLength(191);

        // ✅ Condivisione globale dell'utente loggato e dei suoi ruoli (per Vue)
        Inertia::share('auth.user', function () {
            if (Auth::check()) {
                $user = Auth::user();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profilo' => $user->getRoleNames(), // Collection di stringhe




                ];
            }
            return null;
        });
    }
}

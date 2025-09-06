<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Inertia\Inertia;
use App\Services\DropboxTokenProvider;
use App\Services\StorageService;

// Dropbox / Flysystem v3
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use League\Flysystem\Filesystem as Flysystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // StorageService singleton
        $this->app->singleton(StorageService::class, function () {
            return new StorageService(); // usa default disk da config
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compatibilità vecchi MySQL (opzionale)
        Schema::defaultStringLength(191);

        /**
         * ✅ Estensione driver "dropbox" per Storage
         */
        Storage::extend('dropbox', function ($app, $config) {
            $root = ltrim($config['root'] ?? '', '/'); // rimuove slash iniziale

            // prende access token dinamico via refresh token
            $token = DropboxTokenProvider::getAccessToken();

            try {
                $client     = new DropboxClient($token);
                $adapter    = new DropboxAdapter($client, $root);
                $filesystem = new Flysystem($adapter, $config['options'] ?? []);

                return new FilesystemAdapter($filesystem, $adapter, $config);
            } catch (\Throwable $e) {
                // se il token è scaduto → reset cache e riprova una volta
                if (str_contains($e->getMessage(), 'expired_access_token') || str_contains($e->getMessage(), '401')) {
                    DropboxTokenProvider::forgetCachedToken();

                    $token      = DropboxTokenProvider::getAccessToken();
                    $client     = new DropboxClient($token);
                    $adapter    = new DropboxAdapter($client, $root);
                    $filesystem = new Flysystem($adapter, $config['options'] ?? []);

                    return new FilesystemAdapter($filesystem, $adapter, $config);
                }

                throw $e;
            }
        });

        /**
         * ✅ Condivisione globale dell'utente e dei ruoli per Inertia
         */
        Inertia::share('auth.user', function () {
            if (Auth::check()) {
                $user = Auth::user();

                return [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'profilo' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
                ];
            }

            return null;
        });
    }
}


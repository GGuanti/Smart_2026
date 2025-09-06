<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropboxTokenProvider
{
    protected const CACHE_KEY = 'dropbox_access_token';

    /**
     * Restituisce sempre un access token valido (refresh se scaduto).
     */
    public static function getAccessToken(): string
    {
        // se ho già un token valido in cache → lo uso
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        // altrimenti richiedo un nuovo access token usando il refresh token
        $refreshToken = config('filesystems.disks.dropbox.refresh_token');
        $clientId     = config('filesystems.disks.dropbox.client_id');
        $clientSecret = config('filesystems.disks.dropbox.client_secret');

        if (!$refreshToken || !$clientId || !$clientSecret) {
            throw new \RuntimeException("DropboxTokenProvider: refresh_token / client_id / client_secret mancanti");
        }

        try {
            $response = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (!$response->ok()) {
                Log::error('Dropbox token refresh failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new \RuntimeException("Dropbox token refresh failed: " . $response->body());
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;
            $expiresIn   = $data['expires_in'] ?? 14400; // default 4 ore

            if (!$accessToken) {
                throw new \RuntimeException("Dropbox token refresh response senza access_token");
            }

            // salvo in cache per un tempo leggermente inferiore a expires_in
            Cache::put(self::CACHE_KEY, $accessToken, $expiresIn - 60);

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error("DropboxTokenProvider exception", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Invalida il token in cache.
     */
    public static function forgetCachedToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

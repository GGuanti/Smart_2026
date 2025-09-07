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
     * Usa refresh_token globale da config/filesystems (o services) e cache locale.
     */
    public static function getAccessToken(): string
    {
        if (Cache::has(self::CACHE_KEY)) {
            return (string) Cache::get(self::CACHE_KEY);
        }

        // Leggi credenziali (qui da filesystems; in alternativa da services.dropbox.*)
        $refreshToken = config('filesystems.disks.dropbox.refresh_token');
        $clientId     = config('filesystems.disks.dropbox.client_id');
        $clientSecret = config('filesystems.disks.dropbox.client_secret');

        $refreshToken = preg_replace('/\s+/', '', trim((string) $refreshToken ?? ''));
        $clientId     = trim((string) $clientId ?? '');
        $clientSecret = trim((string) $clientSecret ?? '');

        if ($refreshToken === '' || strlen($refreshToken) < 20 || $clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('DropboxTokenProvider: refresh_token / client_id / client_secret mancanti o non validi');
        }
        if (str_starts_with($refreshToken, 'eyJ')) {
            throw new \RuntimeException('DropboxTokenProvider: refresh_token sembra cifrato; leggere via Eloquent o decriptare prima');
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->retry(2, 200)
                ->post('https://api.dropboxapi.com/oauth2/token', [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if (!$response->ok()) {
                Log::error('Dropbox token refresh failed', [
                    'status'  => $response->status(),
                    'snippet' => substr($response->body(), 0, 200),
                ]);
                throw new \RuntimeException('Dropbox token refresh failed: '.$response->body());
            }

            $data        = $response->json();
            $accessToken = $data['access_token'] ?? null;
            $expiresIn   = (int)($data['expires_in'] ?? 14400);

            if (!$accessToken) {
                throw new \RuntimeException('Dropbox token refresh response senza access_token');
            }

            $ttl = max(60, $expiresIn - 60);
            Cache::put(self::CACHE_KEY, $accessToken, $ttl);

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error('DropboxTokenProvider exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function forgetCachedToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropboxTokenService
{
    /**
     * Restituisce un access token Dropbox valido per l'utente.
     * Se manca o sta per scadere, lo rinnova con il refresh_token.
     */
    public function getAccessToken(User $user): string
    {
        if (!$user->dropbox_refresh_token) {
            throw new \RuntimeException('Dropbox non collegato: manca refresh_token.');
        }

        // Considera in scadenza se mancano <= 120s
        $expiringIn = $user->dropbox_token_expires_at
            ? now()->diffInSeconds($user->dropbox_token_expires_at, false)
            : null;

        $needsRefresh = !$user->dropbox_access_token || $expiringIn === null || $expiringIn <= 120;

        if (!$needsRefresh) {
            return (string) $user->dropbox_access_token;
        }

        // Evita più refresh simultanei (es. più richieste concorrenti)
        $lockKey = 'dropbox_refresh_user_'.$user->getKey();
        $lock    = Cache::lock($lockKey, 10); // 10s di lock
        try {
            if (!$lock->get()) {
                // Qualcun altro sta già rinfrescando: attendi breve e rileggi
                usleep(300 * 1000); // 300ms
                $user->refresh();
                if ($user->dropbox_access_token && $user->dropbox_token_expires_at && now()->lt($user->dropbox_token_expires_at)) {
                    return (string) $user->dropbox_access_token;
                }
                // Se ancora niente, procedi comunque
                $lock->block(5);
            }

            // 1) Sanitizza il refresh token (il cast encrypted lo decripta già via Eloquent)
            $rt = preg_replace('/\s+/', '', trim((string) $user->dropbox_refresh_token));
            if ($rt === '' || strlen($rt) < 20) {
                throw new \RuntimeException('refresh_token assente o troncato: ricollega Dropbox.');
            }
            if (str_starts_with($rt, 'eyJ')) {
                throw new \RuntimeException('refresh_token appare cifrato: leggere tramite Eloquent o decriptare prima.');
            }

            // 2) Log diagnostico minimamente sensibile
            Log::info('Dropbox refresh attempt', [
                'userId'    => $user->id,
                'rt_len'    => strlen($rt),
                'rt_sample' => substr($rt, 0, 6).'...'.substr($rt, -6),
                'client_id' => config('services.dropbox.client_id'),
                'context'   => app()->runningInConsole() ? 'cli' : (request()?->path() ?? 'http'),
            ]);

            // 3) Chiamata refresh con timeout/retry e throw su errore
            $resp = Http::asForm()
                ->timeout(10)
                ->retry(2, 200) // 2 retry con backoff 200ms
                ->post('https://api.dropboxapi.com/oauth2/token', [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $rt,
                    'client_id'     => config('services.dropbox.client_id'),
                    'client_secret' => config('services.dropbox.client_secret'),
                ])
                ->throw();

            $data        = $resp->json();
            $accessToken = $data['access_token'] ?? null;
            $expiresIn   = (int) ($data['expires_in'] ?? 14400); // fallback 4h

            if (!$accessToken) {
                throw new \RuntimeException('Risposta Dropbox senza access_token.');
            }

            // 4) Salva token & expiry (con buffer di 60s)
            $user->forceFill([
                'dropbox_access_token'     => $accessToken,
                'dropbox_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
            ])->save();

            return $accessToken;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Log pulito dello status + snippet
            $res = $e->response;
            Log::error('Dropbox refresh failed', [
                'userId'  => $user->id,
                'status'  => optional($res)->status(),
                'snippet' => substr(optional($res)->body() ?? '', 0, 200),
            ]);
            throw new \RuntimeException('Refresh token Dropbox fallito.');
        } finally {
            optional($lock)->release();
        }
    }
}

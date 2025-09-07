<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class DropboxTokenService
{
    public function getAccessToken(User $user): string
    {
        if (!$user->dropbox_refresh_token) {
            throw new \RuntimeException('Dropbox non collegato: manca refresh_token.');
        }

        $needsRefresh = !$user->dropbox_access_token
            || !$user->dropbox_token_expires_at
            || now()->diffInSeconds($user->dropbox_token_expires_at, false) <= 60;

        if ($needsRefresh) {
            $resp = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => trim($user->dropbox_refresh_token),
                'client_id'     => config('services.dropbox.client_id'),
                'client_secret' => config('services.dropbox.client_secret'),
            ]);

            if (!$resp->ok()) {
                Log::error('Dropbox refresh failed', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                    'userId' => $user->id,
                ]);
                throw new \RuntimeException('Refresh token Dropbox fallito: '.$resp->body());
            }

            $data = $resp->json();
            $user->forceFill([
                'dropbox_access_token'     => $data['access_token'],
                'dropbox_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 14400),
            ])->save();
        }

        return $user->dropbox_access_token;
    }
}

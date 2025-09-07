<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropboxOAuthController extends Controller
{
    public function redirect(Request $r)
    {
        $q = http_build_query([
            'client_id'         => config('services.dropbox.client_id'),
            'response_type'     => 'code',
            'token_access_type' => 'offline', // <-- fondamentale per avere refresh_token
            'scope'             => 'files.content.read files.content.write',
            'redirect_uri'      => config('services.dropbox.redirect'),
            'state'             => csrf_token(),
        ]);

        return redirect("https://www.dropbox.com/oauth2/authorize?$q");
    }

    public function callback(Request $r)
    {
        $code  = $r->query('code');
        abort_if(!$code, 400, 'Missing code');

        $resp = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => config('services.dropbox.client_id'),
            'client_secret' => config('services.dropbox.client_secret'),
            'redirect_uri'  => config('services.dropbox.redirect'),
        ]);

        if (!$resp->ok()) {
            Log::error('Dropbox OAuth exchange failed', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            return redirect()->route('dashboard')
                ->withErrors(['dropbox' => 'OAuth Dropbox fallita: '.$resp->body()]);
        }

        $data = $resp->json();

        if (empty($data['refresh_token'])) {
            return redirect()->route('dashboard')
                ->withErrors(['dropbox' => 'Manca refresh_token (controlla token_access_type=offline e redirect_uri).']);
        }

        $user = $r->user();
        $user->forceFill([
            'dropbox_refresh_token'    => trim($data['refresh_token']),
            'dropbox_access_token'     => $data['access_token'] ?? null,
            'dropbox_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 14400),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Dropbox collegato correttamente!');
    }
}

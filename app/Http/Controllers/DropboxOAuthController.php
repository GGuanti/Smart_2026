<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DropboxOAuthController extends Controller
{
    public function redirect(Request $r)
    {
        // 1) genera e salva lo state in sessione
        $state = Str::random(40);
        $r->session()->put('dropbox_oauth_state', $state);

        $q = http_build_query([
            'client_id'         => config('services.dropbox.client_id'),
            'response_type'     => 'code',
            'token_access_type' => 'offline', // refresh_token
            'scope'             => 'files.content.read files.content.write files.metadata.read',
            'redirect_uri'      => route('dropbox.callback'), // <--- usa la route nominata
            'state'             => $state,
            // 'force_reapprove' => 'true', // opzionale: per forzare re-consent
        ]);

        return redirect("https://www.dropbox.com/oauth2/authorize?$q");
    }

    public function callback(Request $r)
    {
        // 2) valida stato anti-CSRF
        $stateGiven = $r->query('state');
        $stateSaved = $r->session()->pull('dropbox_oauth_state'); // pull = leggi e rimuovi
        if (!$stateGiven || !$stateSaved || !hash_equals($stateSaved, $stateGiven)) {
            abort(400, 'Invalid state');
        }

        $code = $r->query('code');
        abort_if(!$code, 400, 'Missing code');

        // 3) chiama token endpoint con retry/timeout
        $resp = Http::asForm()
            ->timeout(10)
            ->retry(2, 200) // 2 retry, backoff 200ms
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => config('services.dropbox.client_id'),
                'client_secret' => config('services.dropbox.client_secret'),
                'redirect_uri'  => route('dropbox.callback'), // <--- coerente con redirect
            ]);

        if (!$resp->ok()) {
            // 4) log non sensibile
            Log::error('Dropbox OAuth exchange failed', [
                'status' => $resp->status(),
                'snippet' => substr($resp->body(), 0, 200), // non tutto il body
            ]);

            return redirect()
                ->route('dashboard')
                ->withErrors(['dropbox' => 'OAuth Dropbox fallita. Riprova.']);
        }

        $data = $resp->json();

        if (empty($data['refresh_token'])) {
            // capita se manca token_access_type=offline o mismatch redirect
            return redirect()
                ->route('dashboard')
                ->withErrors(['dropbox' => 'Manca refresh_token. Verifica token_access_type=offline e redirect_uri.']);
        }

        // 5) salva in utente (il cast encrypted del model protegge il refresh)
        $user = $r->user();
        $user->forceFill([
            'dropbox_refresh_token'    => trim($data['refresh_token']),
            'dropbox_access_token'     => $data['access_token'] ?? null,
            'dropbox_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 14400),
        ])->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Dropbox collegato correttamente!');
    }
}


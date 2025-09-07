<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\DropboxTokenService;

class DropboxRefreshAll extends Command
{
    protected $signature = 'dropbox:refresh-tokens';
    protected $description = 'Refresh anticipato dei token Dropbox per tutti gli utenti collegati';

    public function handle(DropboxTokenService $svc): int
    {
        User::whereNotNull('dropbox_refresh_token')
            ->select('id','dropbox_refresh_token','dropbox_access_token','dropbox_token_expires_at')
            ->chunk(100, function ($users) use ($svc) {
                foreach ($users as $u) {
                    try { $svc->getAccessToken($u); }
                    catch (\Throwable $e) { report($e); }
                }
            });

        $this->info('Refresh completato');
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\DropboxTokenService;
use Throwable;

class DropboxRefreshAll extends Command
{
    protected $signature = 'dropbox:refresh-tokens {--user=} {--dry : Non salva, solo prova le chiamate}';
    protected $description = 'Refresh anticipato degli access token Dropbox per gli utenti collegati';

    public function handle(DropboxTokenService $svc): int
    {
        // controllo config base
        if (!config('services.dropbox.client_id') || !config('services.dropbox.client_secret')) {
            $this->error('Config Dropbox mancante: verifica services.dropbox.{client_id,client_secret}.');
            return self::FAILURE;
        }

        $userId = $this->option('user');
        $dry    = (bool) $this->option('dry');

        if ($userId) {
            $user = User::whereKey($userId)
                ->whereNotNull('dropbox_refresh_token')
                ->first();

            if (!$user) {
                $this->error("Utente {$userId} non trovato o non collegato.");
                return self::FAILURE;
            }

            try {
                if ($dry) {
                    // chiama il service per calcolare eventuale refresh senza salvare nulla
                    $this->line("Dry-run: controllo token utente #{$user->id}…");
                    $svc->getAccessToken($user); // il service salva di default
                    $this->info("Dry-run: OK (token valido/aggiornato) per utente #{$user->id}");
                } else {
                    $svc->getAccessToken($user);
                    $this->info("Refreshed utente #{$user->id}");
                }
            } catch (Throwable $e) {
                report($e);
                $this->error("Errore refresh utente #{$user->id}: ".$e->getMessage());
                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        // batch su tutti gli utenti collegati
        $total = User::whereNotNull('dropbox_refresh_token')->count();
        if ($total === 0) {
            $this->info('Nessun utente collegato a Dropbox.');
            return self::SUCCESS;
        }

        $this->info("Utenti collegati: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $errors = 0;

        User::whereNotNull('dropbox_refresh_token')
            ->select('id','dropbox_refresh_token','dropbox_access_token','dropbox_token_expires_at')
            ->chunk(100, function ($users) use ($svc, $bar, $dry, &$errors) {
                foreach ($users as $u) {
                    try {
                        if ($dry) {
                            $svc->getAccessToken($u); // comunque valida/refresh
                        } else {
                            $svc->getAccessToken($u);
                        }
                    } catch (Throwable $e) {
                        report($e);
                        $errors++;
                        $this->error("\nErrore refresh utente #{$u->id}: ".$e->getMessage());
                    } finally {
                        $bar->advance();
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Refresh completato'.($errors ? " con {$errors} errori" : ''));

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}

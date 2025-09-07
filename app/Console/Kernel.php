<?php

namespace App\Console\Commands;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Definisce la pianificazione dei comandi.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ogni ora; su Laravel Cloud abilita "Run scheduler" dal pannello
        $schedule->command('dropbox:refresh-tokens')->hourly();
    }

    /**
     * Registra i comandi custom della tua applicazione.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

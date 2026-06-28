<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PresenceReportController extends Controller
{
    public function index()
    {
        // Quante settimane indietro mostrare
        $settimane = 12;
        $from = now()->subWeeks($settimane)->startOfWeek();

        // a) Utenti distinti per settimana
        $distinti = DB::table('user_presence')
            ->selectRaw('YEARWEEK(slot, 3) as settimana, COUNT(DISTINCT user_id) as utenti')
            ->where('slot', '>=', $from)
            ->groupBy('settimana')
            ->orderBy('settimana')
            ->get();

        // b) Picco di concorrenza per settimana
        //    (max utenti contemporanei in uno stesso slot da 5 min)
        $picco = DB::table(
                DB::table('user_presence')
                    ->selectRaw('slot, YEARWEEK(slot, 3) as settimana, COUNT(DISTINCT user_id) as concorrenti')
                    ->where('slot', '>=', $from)
                    ->groupBy('slot')
            , 'sub')
            ->selectRaw('settimana, MAX(concorrenti) as picco')
            ->groupBy('settimana')
            ->orderBy('settimana')
            ->get();

        // Unisco le due serie su un set ordinato di settimane (YEARWEEK -> etichetta leggibile)
        $weeks = collect($distinti->pluck('settimana'))
            ->merge($picco->pluck('settimana'))
            ->unique()
            ->sort()
            ->values();

        $distintiMap = $distinti->keyBy('settimana');
        $piccoMap    = $picco->keyBy('settimana');

        $rows = $weeks->map(function ($yw) use ($distintiMap, $piccoMap) {
            return [
                'settimana' => $this->labelSettimana($yw),  // es. "2026-W26"
                'utenti'    => (int) ($distintiMap[$yw]->utenti ?? 0),
                'picco'     => (int) ($piccoMap[$yw]->picco ?? 0),
            ];
        });

        return Inertia::render('Admin/PresenceReport', [
            'settimane' => $rows,
        ]);
    }

    /**
     * Converte un valore YEARWEEK(mode 3) in etichetta "YYYY-Www".
     */
    private function labelSettimana($yearweek): string
    {
        $s = (string) $yearweek;       // es. "202626"
        $anno = substr($s, 0, 4);
        $sett = substr($s, 4);
        return $anno . '-W' . str_pad($sett, 2, '0', STR_PAD_LEFT);
    }
}

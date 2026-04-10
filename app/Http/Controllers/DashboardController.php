<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

   public function ordiniPerRegione(Request $request)
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $query = DB::table('tab_ordine')
            ->join('tab_costo_trasporto', 'tab_ordine.IdCostoTrasporto', '=', 'tab_costo_trasporto.id')
            ->selectRaw('TRIM(tab_costo_trasporto.regione) as regione, COUNT(*) as totale')
            ->whereNotNull('tab_costo_trasporto.regione')
            ->whereRaw("TRIM(tab_costo_trasporto.regione) <> ''");

        // cambia DataOrdine col tuo campo data reale
        if ($from) {
            $query->whereDate('tab_ordine.DataOrdine', '>=', $from);
        }

        if ($to) {
            $query->whereDate('tab_ordine.DataOrdine', '<=', $to);
        }

        $ordiniPerRegione = $query
            ->groupBy(DB::raw('TRIM(tab_costo_trasporto.regione)'))
            ->orderByDesc('totale')
            ->get()
            ->map(function ($row) {
                $row->regione = $this->normalizzaNomeRegione($row->regione);
                $row->totale = (int) $row->totale;
                return $row;
            })
            ->values();

        $totaleOrdini = $ordiniPerRegione->sum('totale');
        $regioneTop = $ordiniPerRegione->first();

        return Inertia::render('Dashboard/OrdiniPerRegione', [
            'ordiniPerRegione' => $ordiniPerRegione,
            'totaleOrdini' => $totaleOrdini,
            'totaleRegioniAttive' => $ordiniPerRegione->count(),
            'regioneTop' => $regioneTop?->regione,
            'maxOrdini' => $ordiniPerRegione->max('totale') ?? 0,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    private function normalizzaNomeRegione(?string $nome): string
    {
        $nome = trim((string) $nome);
        $nome = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');

        $mappa = [
            "Valle D'Aosta" => "Valle d'Aosta",
            "Trentino Alto Adige" => "Trentino-Alto Adige",
            "Friuli Venezia Giulia" => "Friuli-Venezia Giulia",
            "Emilia Romagna" => "Emilia-Romagna",
        ];

        return $mappa[$nome] ?? $nome;
    }








    public function index(Request $request)
    {
        $user = Auth::user();

        $allowed = [
            'info@isomaxporte.com',
            'm.mecca@isomaxporte.com',
            'gguanti@gmail.com',
            'giuseppe.mecca@isomaxporte.com',
        ];

        $isAdmin = in_array($user->email, $allowed);

        // 🔥 DEFAULT SETTIMANA
        if (!$request->from || !$request->to) {
            $start = Carbon::now()->startOfWeek(1);
            $end   = Carbon::now()->endOfWeek(0);

            return redirect()->route('dashboard', [
                'from' => $start->format('Y-m-d'),
                'to'   => $end->format('Y-m-d'),
            ]);
        }

        $from = $request->from;
        $to   = $request->to;

        // =========================
        // 🔥 BASE QUERY (SETTIMANA)
        // =========================

        $base = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine')
            ->whereBetween('o.DataOrdine', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay()
            ]);

        // =========================
        // ✅ KPI
        // =========================

        $totaleFatturato = (clone $base)
            ->where('o.TipoDoc', 'Consegnato')
            ->selectRaw('
                SUM(
                    e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
                    * (1 - IFNULL(o.Sconto1,0)/100)
                    * (1 - IFNULL(o.Sconto2,0)/100)
                ) as totale
            ')
            ->value('totale');

        $totaleOrdini = DB::table('crm_03.tab_ordine')
            ->whereNotNull('DataOrdine')
            ->whereBetween('DataOrdine', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay()
            ])
            ->count();

        $totaleUtenti = User::count();

        // =========================
        // ✅ GRAFICO TORTA
        // =========================

        $ordiniPerTipo = (clone $base)
            ->select(
                'o.TipoDoc',
                DB::raw('
                    SUM(
                        e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
                        * (1 - IFNULL(o.Sconto1,0)/100)
                        * (1 - IFNULL(o.Sconto2,0)/100)
                    ) as totale
                ')
            )
            ->groupBy('o.TipoDoc')
            ->orderByDesc('totale')
            ->get();

        // =========================
        // ✅ ANDAMENTO DA INIZIO ANNO (🔥 FIX)
        // =========================

        $startYear = Carbon::now()->startOfYear();
        $today     = Carbon::now();
        $andamentoMensileTipo = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine')
            ->whereBetween('o.DataOrdine', [
                $startYear->startOfDay(),
                $today->endOfDay()
            ])
            ->selectRaw('
        DATE_FORMAT(o.DataOrdine, "%Y-%m") as mese,
        o.TipoDoc,
        SUM(
            e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
            * (1 - IFNULL(o.Sconto1,0)/100)
            * (1 - IFNULL(o.Sconto2,0)/100)
        ) as totale
    ')
            ->groupBy(DB::raw('DATE_FORMAT(o.DataOrdine, "%Y-%m"), o.TipoDoc'))
            ->orderBy('mese')
            ->get();

        $andamentoMensile = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine')
            ->whereBetween('o.DataOrdine', [
                $startYear->startOfDay(),
                $today->endOfDay()
            ])
            ->selectRaw('
                DATE_FORMAT(o.DataOrdine, "%Y-%m") as mese,
                SUM(
                    e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
                    * (1 - IFNULL(o.Sconto1,0)/100)
                    * (1 - IFNULL(o.Sconto2,0)/100)
                ) as totale
            ')
            ->groupBy(DB::raw('DATE_FORMAT(o.DataOrdine, "%Y-%m")'))
            ->orderBy('mese')
            ->get();

        // =========================
        // ✅ CONFRONTO MENSILE
        // =========================

        $baseMensile = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine');

        $startMonth = Carbon::now()->startOfMonth();
        $endMonth   = Carbon::now()->endOfMonth();

        $corrente = (clone $baseMensile)
            ->whereBetween('o.DataOrdine', [
                $startMonth->startOfDay(),
                $endMonth->endOfDay()
            ])
            ->selectRaw('
                SUM(
                    e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
                    * (1 - IFNULL(o.Sconto1,0)/100)
                    * (1 - IFNULL(o.Sconto2,0)/100)
                ) as totale
            ')
            ->value('totale');

        $startPrev = Carbon::now()->subMonth()->startOfMonth();
        $endPrev   = Carbon::now()->subMonth()->endOfMonth();

        $precedente = (clone $baseMensile)
            ->whereBetween('o.DataOrdine', [
                $startPrev->startOfDay(),
                $endPrev->endOfDay()
            ])
            ->selectRaw('
                SUM(
                    e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))
                    * (1 - IFNULL(o.Sconto1,0)/100)
                    * (1 - IFNULL(o.Sconto2,0)/100)
                ) as totale
            ')
            ->value('totale');

        // =========================
        // 🚀 RETURN
        // =========================

        return Inertia::render('Dashboard', [
            'kpi' => [
                'fatturato' => $totaleFatturato ?? 0,
                'ordini'    => $totaleOrdini,
                'utenti'    => $totaleUtenti,
            ],
            'ordiniPerTipo' => $ordiniPerTipo,
            'andamentoMensile' => $andamentoMensile,
            'andamentoMensileTipo' => $andamentoMensileTipo,
            'confrontoMensile' => [
                'corrente'   => $corrente ?? 0,
                'precedente' => $precedente ?? 0,
            ],
            'filters' => [
                'from' => $from,
                'to'   => $to,
            ],
            'isAdmin' => $isAdmin,
        ]);
    }
}

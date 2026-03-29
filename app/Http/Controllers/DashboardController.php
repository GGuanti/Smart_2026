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
        // 🔥 DEFAULT SETTIMANA SE NON PASSATA
        if (!$request->from || !$request->to) {
        $start = Carbon::now()->startOfWeek(1); // lunedì
$end = Carbon::now()->endOfWeek(0);     // domenica
            return redirect()->route('dashboard', [
                'from' => $start->format('Y-m-d'),
                'to' => $end->format('Y-m-d'),
            ]);
        }

        $from = $request->from;
        $to = $request->to;

        // 🔥 BASE QUERY
        $base = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine')
            ->whereRaw("DATE(o.DataOrdine) BETWEEN ? AND ?", [$from, $to]);

        // =========================
        // ✅ KPI
        // =========================

        $totaleFatturato = (clone $base)
            ->sum(DB::raw('e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))'));

        $totaleOrdini = DB::table('crm_03.tab_ordine')
            ->whereNotNull('DataOrdine')
            ->whereRaw("DATE(DataOrdine) BETWEEN ? AND ?", [$from, $to])
            ->count();

        $totaleUtenti = User::count();

        // =========================
        // ✅ GRAFICO TORTA
        // =========================

        $ordiniPerTipo = (clone $base)
            ->select(
                'o.TipoDoc',
                DB::raw('SUM(e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))) as totale')
            )
            ->groupBy('o.TipoDoc')
            ->orderByDesc('totale')
            ->get();

        // =========================
        // ✅ CONFRONTO MENSILE
        // =========================

        $startCurrent = Carbon::now()->startOfMonth();
        $endCurrent = Carbon::now()->endOfMonth();

        $startPrev = Carbon::now()->subMonth()->startOfMonth();
        $endPrev = Carbon::now()->subMonth()->endOfMonth();

        $baseMensile = DB::table('crm_03.tab_ordine as o')
            ->join('crm_03.tab_elementi_ordine as e', 'o.Nordine', '=', 'e.Nordine')
            ->whereNotNull('o.DataOrdine');

        $corrente = (clone $baseMensile)
            ->whereRaw("DATE(o.DataOrdine) BETWEEN ? AND ?", [
                $startCurrent->format('Y-m-d'),
                $endCurrent->format('Y-m-d')
            ])
            ->select(DB::raw('SUM(e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))) as totale'))
            ->value('totale');

        $precedente = (clone $baseMensile)
            ->whereRaw("DATE(o.DataOrdine) BETWEEN ? AND ?", [
                $startPrev->format('Y-m-d'),
                $endPrev->format('Y-m-d')
            ])
            ->select(DB::raw('SUM(e.Qta * (IFNULL(e.PrezzoCad,0) + IFNULL(e.PrezzoMan,0))) as totale'))
            ->value('totale');

        // =========================
        // 🚀 RETURN
        // =========================

        return Inertia::render('Dashboard', [
            'kpi' => [
                'fatturato' => $totaleFatturato ?? 0,
                'ordini' => $totaleOrdini,
                'utenti' => $totaleUtenti,
            ],
            'ordiniPerTipo' => $ordiniPerTipo,
            'confrontoMensile' => [
                'corrente' => $corrente ?? 0,
                'precedente' => $precedente ?? 0,
            ],
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'isAdmin' => $isAdmin,
        ]);
    }
}

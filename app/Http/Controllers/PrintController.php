<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintController extends Controller
{
    public function Settimana(int $year, int $week, Request $request)
    {
        // ISO week: settimana europea (lun-dom)
        $start = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end   = (clone $start)->addDays(6)->endOfDay();

        // ✅ Prendo gli ordini dell’utente loggato nella settimana (DataInizio)
        $appointments = Appointment::query()
            ->with(['items']) // relazione hasMany items
            ->where('user_id', Auth::id())
            ->where('status', 'Pianificato')
            ->whereDate('DataInizio', '>=', $start->toDateString())
            ->whereDate('DataInizio', '<=', $end->toDateString())
            ->orderBy('DataInizio')
            ->orderBy('Nordine')
            ->get();

        // Facoltativo: raggruppa per Nordine (se hai più record con stesso Nordine)
        // Se ogni appointment è già un ordine unico, puoi anche saltare questo.
        $orders = $appointments->groupBy('Nordine')->map(function ($group) {
            // prendo la “testata” dal primo
            $head = $group->first();

            // unisco tutte le righe items (se ce ne sono più record)
            $items = $group->flatMap(fn($a) => $a->items ?? collect())->values();

            return [
                'head' => $head,
                'items' => $items,
            ];
        })->values();

        $data = [
            'year' => $year,
            'week' => $week,
            'start' => $start,
            'end' => $end,
            'orders' => $orders,
            'user' => Auth::user(),
            'printedAt' => now(),
        ];

        $pdf = Pdf::loadView('pdf.SettimanaOrdini', $data)
            ->setPaper('a4', 'portrait');

        $filename = sprintf('Settimana_S%02d_%d.pdf', $week, $year);

        // inline = apre nel browser; download = scarica
        return $request->boolean('download', true)
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}

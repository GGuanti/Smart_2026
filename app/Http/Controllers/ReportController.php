<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Anagrafica;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Anteprima PDF giornate
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'codCliente'   => 'required|string',
            'dataInizio'   => 'nullable|date',
            'dataFine'     => 'nullable|date',
        ]);

        // Prepara valori per whereBetween
        $dataInizio = $validated['dataInizio'] ?? '1900-01-01';
        $dataFine   = $validated['dataFine'] ?? Carbon::now()->toDateString();

        // Giornate filtrate con Query Builder
        $giornate = DB::table('VistaReportGiornate')
            ->where('CodCliente', $validated['codCliente'])
            ->whereBetween('Data', [$dataInizio, $dataFine])
            ->orderBy('Data')
            ->get();




        // Dati anagrafica
        $cliente = Anagrafica::where('CodCliente', $validated['codCliente'])->first();

        // Genera PDF
        $pdf = Pdf::loadView('Reports.giornate_pdf', [
            'giornate'    => $giornate,
            'cliente'     => $cliente,
            'dataInizio'  => $dataInizio,
            'dataFine'    => $dataFine,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("report_giornate_{$validated['codCliente']}.pdf");
    }
}

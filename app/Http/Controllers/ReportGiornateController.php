<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Anagrafica;
use App\Mail\ReportGiornateMail;
use Illuminate\Support\Facades\Auth;
class ReportGiornateController extends Controller
{
    /**
     * GET /report/giornate/preview
     * Mostra il PDF inline nel browser.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'codCliente' => 'required|string',
            'dataInizio' => 'nullable|date',
            'dataFine'   => 'nullable|date',
        ]);

        $dataInizio = $validated['dataInizio'] ?? '1900-01-01';
        $dataFine   = $validated['dataFine'] ?? Carbon::now()->toDateString();

        $giornate = $this->getGiornate($validated['codCliente'], $dataInizio, $dataFine);
        $cliente  = Anagrafica::where('CodCliente', $validated['codCliente'])->first();

        if ($giornate->isEmpty()) {
            return response('Nessun dato per il periodo selezionato.', 204);
        }

        $pdf = Pdf::loadView('Reports.giornate_pdf', [
            'giornate'   => $giornate,
            'cliente'    => $cliente,
            'codCliente' => $validated['codCliente'],
            'dataInizio' => $dataInizio,
            'dataFine'   => $dataFine,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("report_giornate_{$validated['codCliente']}.pdf");
    }

    /**
     * POST /report/giornate/email
     * Genera e invia il PDF via email.
     */
    public function generaEInviaEmail(Request $request)
    {
        try {
            $data = $request->validate([
                'codCliente' => 'required|string',
                'email'      => 'required|email',
                'dataInizio' => 'nullable|date',
                'dataFine'   => 'nullable|date',
            ]);

            $giornate = $this->getGiornate($data['codCliente'], $data['dataInizio'] ?? null, $data['dataFine'] ?? null);
            $cliente  = Anagrafica::where('CodCliente', $data['codCliente'])->first();

            if ($giornate->isEmpty()) {
                return response()->json(['ok' => false, 'message' => 'Nessun dato da inviare per il periodo.'], 422);
            }

            if (!view()->exists('Reports.giornate_pdf')) {
                throw new \RuntimeException('Template PDF non trovato: Reports.giornate_pdf');
            }

            // Salva PDF
            Storage::makeDirectory('reports');
            $relativePath = 'reports/'.$data['codCliente'].'-giornate.pdf';
            $absolutePath = Storage::path($relativePath);

            $pdf = Pdf::loadView('Reports.giornate_pdf', [
                'codCliente' => $data['codCliente'],
                'dataInizio' => $data['dataInizio'] ?? null,
                'dataFine'   => $data['dataFine'] ?? null,
                'giornate'   => $giornate,
                'cliente'    => $cliente,
            ])->setPaper('A4', 'portrait');

            Storage::put($relativePath, $pdf->output());

            if (!is_file($absolutePath)) {
                throw new \RuntimeException('Impossibile scrivere il PDF: '.$absolutePath);
            }

            // Invia email con allegato
            Mail::to($data['email'])
                    ->send(
                new ReportGiornateMail(
                    $absolutePath,
                    $data['codCliente'],
                    $data['dataInizio'] ?? null,
                    $data['dataFine'] ?? null,
                    $cliente,
                    Auth::user()->name,
                    $cliente->I_Cognome ?? '', // cognome cliente
                    $cliente->H_Nome ?? ''     // nome cliente
                )
            );

            return response()->json(['ok' => true, 'path' => $relativePath]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Dati non validi', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Errore invio report giornate', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Query VistaReportGiornate + mapping chiavi.
     */
    private function getGiornate(string $codCliente, ?string $dataInizio, ?string $dataFine)
    {
        $q = DB::table('VistaReportGiornate')->where('CodCliente', $codCliente);

        if ($dataInizio && $dataFine) {
            $start = Carbon::parse($dataInizio)->startOfDay()->toDateTimeString();
            $end   = Carbon::parse($dataFine)->endOfDay()->toDateTimeString();
            $q->whereBetween('Data', [$start, $end]);
        } elseif ($dataInizio) {
            $start = Carbon::parse($dataInizio)->startOfDay()->toDateTimeString();
            $q->where('Data', '>=', $start);
        } elseif ($dataFine) {
            $end = Carbon::parse($dataFine)->endOfDay()->toDateTimeString();
            $q->where('Data', '<=', $end);
        }

        $rows = $q->orderBy('Data', 'asc')->get();

        if ($rows->isNotEmpty()) {
            Log::info('Chiavi VistaReportGiornate', [
                'keys' => array_keys(get_object_vars($rows->first()))
            ]);
        }

        return $rows->map(function ($r) {
            $obj = (object) [
                'IDContratto'  => $r->IDContratto
                                   ?? ($r->id_contratto ?? null)
                                   ?? ($r->CodAttivita ?? null),
                'TipoContr'    => $r->TipoContr
                                   ?? ($r->TipoContratto ?? null)
                                   ?? ($r->Tipo ?? null),
                'Data'         => $r->Data ?? null,
                'Retribuzione' => $r->Retribuzione
                                   ?? ($r->RetribuzioneLorda ?? null)
                                   ?? ($r->retribuzione ?? 0),
                'DIARIA'       => $r->DIARIA
                                   ?? ($r->Diaria ?? null)
                                   ?? ($r->diaria ?? 0),
            ];

            // Normalizza tipi
            $obj->Retribuzione = is_numeric($obj->Retribuzione) ? (float)$obj->Retribuzione : 0.0;
            $obj->DIARIA       = (int) (is_bool($obj->DIARIA) ? $obj->DIARIA : (is_numeric($obj->DIARIA) ? $obj->DIARIA : 0));

            return $obj;
        });
    }
}

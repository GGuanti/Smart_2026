<?php

namespace App\Http\Controllers;

use App\Mail\OrdineConfermaMail;
use App\Models\TabOrdine;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class OrdineReportController extends Controller
{

    public function conferma(Request $request,$id)
    {
        $ordine = TabOrdine::findOrFail($id);
        abort_if($ordine->user_id !== auth()->id(), 403);
        $TipoStampa = $request->get('tipo', 'conferma');
        // ✅ RIGHE collegate via Nordine (non via ID)
        $righe = DB::table('tab_elementi_ordine as e')
            ->leftJoin('listini as l', 'l.id_listino', '=', 'e.IdModello')
            ->leftJoin('finitura_anta as fa', 'fa.IdFinAnta', '=', 'e.IdColAnta')
            ->leftJoin('finitura_telaio as ft', 'ft.IdFinTelaio', '=', 'e.IdColTelaio')
            ->leftJoin('tab_soluzioni as s', 's.id_tab_soluzioni', '=', 'e.IdSoluzione')
            ->leftJoin('tipo_telaio as tt', 'tt.id_tipo_telaio', '=', 'e.IdTipTelaio')
            ->leftJoin('tab_serratura as se', 'se.id_serratura', '=', 'e.IdSerratura')
            ->leftJoin('tab_cerniere as ce', 'ce.id_col_ferr', '=', 'e.IdColFerr')
            ->leftJoin('tab_aperture as ap', 'ap.IdApertura', '=', 'e.IdApertura')
            ->leftJoin('tab_vetri as v', 'v.id_vetro', '=', 'e.IdVetro')
            ->where('e.Nordine', $ordine->Nordine)
            ->select([
                'e.*',

                // modello per immagine
                'l.nome_modello as nome_modello',

                // colore anta
                'fa.Colore as ColoreAnta',
                'ft.Colore as ColoreTelaio',
                's.soluzione as TipoSoluzione',
                'tt.stipite as TipoTelaio',
                'se.des_serratura as Serratura',
                'ce.des_cernira as Cerniere',
                'ap.des as Verso',
                'v.des_vetro as Vetro',


            ])
            ->get();
        // (opzionale) totali
        $totaleImponibile  = (float)($ordine->TotaleImponibile ?? 0);
        $totaleIva         = (float)($ordine->TotaleIva ?? 0);
        $totaleComplessivo = (float)($ordine->TotaleComplessivo ?? ($totaleImponibile + $totaleIva));
        if ($TipoStampa === 'Prev') {
            $NomeReport = 'reportisomax.preventivo_isomax';
        } else {
            $NomeReport = 'reportisomax.conferma_isomax';
        }
        return Pdf::loadView($NomeReport, [
            'ordine' => $ordine,
            'righe' => $righe,
            'utente' => optional(auth()->user())->name,
            'totaleImponibile' => $totaleImponibile,
            'totaleIva' => $totaleIva,
            'totaleComplessivo' => $totaleComplessivo,
        ])->stream('Conferma_Ordine_' . $ordine->Nordine . '.pdf');
    }
    public function emailConferma(Request $request, TabOrdine $ordine)
    {
        $data = $request->validate([
            'to' => ['nullable', 'email'],
        ]);

        $to = $data['to'] ?? $ordine->Email ?? null;
        if (!$to) {
            return response()->json([
                'message' => 'Email destinatario mancante (campo Email).'
            ], 422);
        }
        $righe = DB::table('tab_elementi_ordine as e')
            ->leftJoin('listini as l', 'l.id_listino', '=', 'e.IdModello')
            ->leftJoin('finitura_anta as fa', 'fa.IdFinAnta', '=', 'e.IdColAnta')
            ->leftJoin('finitura_telaio as ft', 'ft.IdFinTelaio', '=', 'e.IdColTelaio')
            ->leftJoin('tab_soluzioni as s', 's.id_tab_soluzioni', '=', 'e.IdSoluzione')
            ->leftJoin('tipo_telaio as tt', 'tt.id_tipo_telaio', '=', 'e.IdTipTelaio')
            ->leftJoin('tab_serratura as se', 'se.id_serratura', '=', 'e.IdSerratura')
            ->leftJoin('tab_cerniere as ce', 'ce.id_col_ferr', '=', 'e.IdColFerr')
            ->leftJoin('tab_aperture as ap', 'ap.IdApertura', '=', 'e.IdApertura')
            ->leftJoin('tab_vetri as v', 'v.id_vetro', '=', 'e.IdVetro')
            ->where('e.Nordine', $ordine->Nordine)
            ->select([
                'e.*',

                // modello per immagine
                'l.nome_modello as nome_modello',

                // colore anta
                'fa.Colore as ColoreAnta',
                'ft.Colore as ColoreTelaio',
                's.soluzione as TipoSoluzione',
                'tt.stipite as TipoTelaio',
                'se.des_serratura as Serratura',
                'ce.des_cernira as Cerniere',
                'ap.des as Verso',
                'v.des_vetro as Vetro',


            ])
            ->get();
        $totaleImponibile  = (float)($ordine->TotaleImponibile ?? 0);
        $totaleIva         = (float)($ordine->TotaleIva ?? 0);
        $totaleComplessivo = (float)($ordine->TotaleComplessivo ?? ($totaleImponibile + $totaleIva));


        try {
            $pdf = Pdf::loadView('reportisomax.conferma_isomax', [
                'ordine' => $ordine,
                'righe' => $righe,
                'utente' => optional(auth()->user())->name,
                'totaleImponibile' => $totaleImponibile,
                'totaleIva' => $totaleIva,
                'totaleComplessivo' => $totaleComplessivo,
            ])->setPaper('a4');

            $pdfBytes = $pdf->output();
            $filename = 'Conferma_Ordine_' . ($ordine->Nordine ?? $ordine->ID) . '.pdf';

            Mail::to($to)->send(new OrdineConfermaMail($ordine, $pdfBytes, $filename));

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Invio email conferma ordine fallito', [
                'ordine_id' => $ordine->ID ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Errore invio email. Controlla log e configurazione SMTP.'
            ], 500);
        }
    }
}

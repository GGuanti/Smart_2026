<?php

namespace App\Http\Controllers;

use App\Mail\OrdineConfermaMail;
use App\Models\TabOrdine;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class OrdineReportController extends Controller
{

    public function conferma(Request $request, $id)
    {
        // $ordine = TabOrdine::findOrFail($id);
        $ordine = TabOrdine::query()
            ->leftJoin('tab_trasporto as tr', 'tr.id', '=', 'tab_ordine.IdTrasporto')
            ->select(
                'tab_ordine.*',
                'tab_ordine.TxtModPagamento as Pagamento',
                'tab_ordine.DataCons as ConsegnaRichiesta',
                'tr.des as trasporto_des'
            )
            ->where('tab_ordine.ID', $id)
            ->firstOrFail();
        $userId = $request->user()->id; // se la route è protetta da auth
        abort_if($ordine->user_id !== $userId, 403);
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
                'l.dis_libro_simm as dis_libro_simm',
                // colore anta
                'fa.Colore as ColoreAnta',
                'ft.Colore as ColoreTelaio',
                's.soluzione as TipoSoluzione',
                's.ass_collistino as ass_collistino',
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
        $userDoc = User::select('id', 'name', 'datiazienda', 'logo_path')
            ->find($ordine->user_id);
        return Pdf::loadView($NomeReport, [
            'ordine' => $ordine,
            'righe' => $righe,
            'userDoc' => $userDoc,
            'utente' => $request->user()?->name,
            'totaleImponibile' => $totaleImponibile,
            'totaleIva' => $totaleIva,
            'totaleComplessivo' => $totaleComplessivo,
        ])->stream('Conferma_Ordine_' . $ordine->Nordine . '.pdf');
    }
   public function emailConferma(Request $request, TabOrdine $ordine)
    {
        // Auth obbligatoria (route protetta da middleware auth)
        abort_if($ordine->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'to'   => ['nullable', 'email'],
            'tipo' => ['nullable', 'string'], // opzionale, se arriva nel body
        ]);

        // tipo può arrivare da route o da body/query
        $TipoStampa = $request->route('tipo') ?? $request->input('tipo') ?? 'ConfOrd';
        $TipoStampa = ($TipoStampa === 'Prev') ? 'Prev' : 'ConfOrd';

        $to = $data['to'] ?? $ordine->Email ?? null;
        if (!$to && $TipoStampa === 'Prev') {
            return response()->json([
                'message' => 'Email destinatario mancante (campo Email).'
            ], 422);
        }

        $userDoc = User::select('id', 'name', 'datiazienda', 'logo_path')
            ->find($ordine->user_id);

        $utente = $request->user()?->name ?? 'Sistema';

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
                'l.nome_modello as nome_modello',
                'l.dis_libro_simm as dis_libro_simm',
                'fa.Colore as ColoreAnta',
                'ft.Colore as ColoreTelaio',
                's.soluzione as TipoSoluzione',
                's.ass_collistino as ass_collistino',
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

        $NomeReport = $TipoStampa === 'Prev'
            ? 'reportisomax.preventivo_isomax'
            : 'reportisomax.conferma_isomax';

        // View email coerente col tipo
        $viewMail = $TipoStampa === 'Prev'
            ? 'emails.prev_conferma'
            : 'emails.ordine_conferma';

        if (!view()->exists($viewMail)) {
            throw new \RuntimeException("View mail non trovata: {$viewMail}");
        }

        try {
            $pdf = Pdf::loadView($NomeReport, [
                'ordine'            => $ordine,
                'righe'             => $righe,
                'userDoc'           => $userDoc,
                'utente'            => $utente,
                'totaleImponibile'  => $totaleImponibile,
                'totaleIva'         => $totaleIva,
                'totaleComplessivo' => $totaleComplessivo,
            ])->setPaper('a4');

            $pdfBytes = $pdf->output();
            $filename = ($TipoStampa === 'Prev' ? 'Preventivo_' : 'Conferma_Ordine_') . ($ordine->Nordine ?? $ordine->ID) . '.pdf';

            if ($TipoStampa === 'ConfOrd') {
                Mail::to('info@isomaxporte.com')
                    ->cc('giuseppe.guanti@smartit.coop')
                    ->send(new OrdineConfermaMail($ordine, $pdfBytes, $filename, $utente, $viewMail));
            } else {
                Mail::to($to)
                    ->send(new OrdineConfermaMail($ordine, $pdfBytes, $filename, $utente, $viewMail));
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Invio email conferma ordine fallito', [
                'ordine_id' => $ordine->ID ?? null,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Errore invio email. Controlla log e configurazione SMTP.'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TabOrdine;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
class OrdineReportController extends Controller
{
    public function conferma($id)
    {
        $ordine = TabOrdine::findOrFail($id);
        abort_if($ordine->user_id !== auth()->id(), 403);

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
    logger()->info('RIGA_COLOR', ['IdColAnta' => $righe[0]->IdColAnta ?? null, 'ColoreAnta' => $righe[0]->ColoreAnta ?? null]);
    logger()->info('RIGA_COLOR', [
        'IdColAnta' => $righe[0]->IdColAnta ?? null,
        'ColoreAnta' => $righe[0]->ColoreAnta ?? null,
      ]);
            logger()->info('RIGA_FIRST', ['row' => (array)($righe->first() ?? [])]);
            logger()->info('ORDINE', ['ID' => $ordine->ID, 'Nordine' => $ordine->Nordine]);
            logger()->info('RIGHE_COUNT', ['count' => $righe->count()]);
        // (opzionale) totali
        $totaleImponibile  = (float)($ordine->TotaleImponibile ?? 0);
        $totaleIva         = (float)($ordine->TotaleIva ?? 0);
        $totaleComplessivo = (float)($ordine->TotaleComplessivo ?? ($totaleImponibile + $totaleIva));

        return Pdf::loadView('reports.ordini.conferma_isomax', [
            'ordine' => $ordine,
            'righe' => $righe,
            'utente' => optional(auth()->user())->name,
            'totaleImponibile' => $totaleImponibile,
            'totaleIva' => $totaleIva,
            'totaleComplessivo' => $totaleComplessivo,
        ])->stream('Conferma_Ordine_' . $ordine->Nordine . '.pdf');
    }
}

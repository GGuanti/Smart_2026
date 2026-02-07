<?php

namespace App\Http\Controllers;

use App\Models\TabOrdine;
use App\Models\TabElementiOrdine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Listino;
use App\Models\FinituraAnta;
use App\Models\FinituraTelaio;
use App\Models\TabSoluzione;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PreventivoController extends Controller
{

    public function create(TabOrdine $ordine)
    {
        abort_if($ordine->user_id !== Auth::id(), 403);

        $elementi = TabElementiOrdine::query()
            ->where('Nordine', $ordine->Nordine)
            ->orderBy('Id') // se la PK è diversa, cambiala (es. ID)
            ->get();

        $modelli = DB::table('listini')->get();
        $soluzioni = DB::table('tab_soluzioni')
        ->orderBy('soluzione')
        ->get();

        $colAnta = DB::table('finitura_anta')->get();
        $colTelaio = DB::table('finitura_telaio')->get();
        $maniglie = DB::table('tab_maniglie')->get();
        $aperture = DB::table('tab_aperture')->get();
        $tipiTelaio = DB::table('tipo_telaio')->get();
        $serrature = DB::table('tab_serratura')->get();
        $cerniere = DB::table('tab_cerniere')->get();
        $vetri = DB::table('tab_vetri')->get();
        $assModVetri = DB::table('ass_mod_vetri')->get();
        $imbotte = DB::table('tab_imbotte')->get();


        return Inertia::render('Preventivi/Form', [
            'ordine'   => $ordine,
            'elementi' => $elementi, // ✅ tutte le righe già presenti
            // ✅ MODELLI DA LISTINI
            'modelli' => $modelli,
            'soluzioni' => $soluzioni,

            'colAnta' => $colAnta,
            'colTelaio' => $colTelaio,
            'maniglie' => $maniglie,
            'aperture' => $aperture,
            'tipiTelaio' => $tipiTelaio,
            'serrature' => $serrature,
            'cerniere' => $cerniere,
            'vetri' => $vetri,
            'assModVetri' => $assModVetri,
            'imbotte' => $imbotte,


        ]);
    }
    public function store(Request $request, TabOrdine $ordine)
    {
        $data = $request->validate([
            'righe' => ['required', 'array', 'min:1'],
            'righe.*.DimL' => 'nullable|numeric',
            'righe.*.DimA' => 'nullable|numeric',
            'righe.*.DimSp' => 'nullable|numeric',
            'righe.*.Qta' => 'nullable|numeric|min:0',
            'righe.*.PrezzoCad' => 'nullable|numeric|min:0',
            'righe.*.PrezzoMan' => 'nullable|numeric|min:0',
            'righe.*.NoteMan' => 'nullable|string|max:255',
            'righe.*.PercFile' => 'nullable|string|max:255',
            'righe.*.IdModello' => 'nullable|integer',
            'righe.*.IdColAnta' => 'nullable|integer',
            'righe.*.IdColTelaio' => 'nullable|integer',
            'righe.*.IdSoluzione' => 'nullable|integer',
            'righe.*.IdManiglia' => 'nullable|integer',
            'righe.*.IdApertura' => 'nullable|integer',
            'righe.*.IdTipTelaio' => 'nullable|integer',
            'righe.*.IdVetro' => 'nullable|integer',
            'righe.*.IdColFerr' => 'nullable|integer',
            'righe.*.IdSerratura' => 'nullable|integer',
            'righe.*.IdImbotte' => 'nullable|integer',
            'righe.*.CkTaglioObl' => 'nullable|string|max:255',
            'righe.*.TxtCassMet'  => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $ordine) {

            $idsClient = [];

            foreach ($data['righe'] as $r) {

                // sicurezza: forza Nordine dal padre
                $r['Nordine'] = $ordine->Nordine;

                if (!empty($r['Id'])) {
                    // ✅ UPDATE (solo se appartiene allo stesso ordine)
                    $el = TabElementiOrdine::where('Id', $r['Id'])
                        ->where('Nordine', $ordine->Nordine)
                        ->firstOrFail();

                    $el->fill($r);
                    $el->save();

                    $idsClient[] = $el->Id;
                } else {
                    // ✅ INSERT
                    $el = TabElementiOrdine::create($r);
                    $idsClient[] = $el->Id;
                }
            }

            // ✅ opzionale: elimina dal DB le righe che non esistono più nel client
            TabElementiOrdine::where('Nordine', $ordine->Nordine)
                ->whereNotIn('Id', $idsClient)
                ->delete();
        });

        return back()->with('success', 'Preventivo salvato');
    }
    public function store1(Request $request, TabOrdine $ordine)
    {
abort_if($ordine->user_id !== Auth::id(), 403);

        // ✅ se arriva il nuovo form: righe[]
        if ($request->has('righe') && is_array($request->input('righe'))) {

            $validated = $request->validate([
                'righe' => ['required', 'array', 'min:1'],
                'righe.*.DimL' => 'nullable|numeric',
                'righe.*.DimA' => 'nullable|numeric',
                'righe.*.DimSp' => 'nullable|numeric',
                'righe.*.Qta' => 'nullable|numeric|min:0',
                'righe.*.PrezzoCad' => 'nullable|numeric|min:0',
                'righe.*.PrezzoMan' => 'nullable|numeric|min:0',
                'righe.*.NoteMan' => 'nullable|string|max:255',
                'righe.*.PercFile' => 'nullable|string|max:255',
                'righe.*.IdModello' => 'nullable|integer',
                'righe.*.IdColAnta' => 'nullable|integer',
                'righe.*.IdColTelaio' => 'nullable|integer',
                'righe.*.IdSoluzione' => 'nullable|integer',
                'righe.*.IdManiglia' => 'nullable|integer',
                'righe.*.IdApertura' => 'nullable|integer',
                'righe.*.IdTipTelaio' => 'nullable|integer',
                'righe.*.IdVetro' => 'nullable|integer',
                'righe.*.IdColFerr' => 'nullable|integer',
                'righe.*.IdSerratura' => 'nullable|integer',
                'righe.*.IdImbotte' => 'nullable|integer',
                'righe.*.CkTaglioObl' => 'nullable|string|max:255',
                'righe.*.TxtCassMet'  => 'nullable|string|max:255',
            ]);

            foreach ($validated['righe'] as $riga) {
                // 🔒 Nordine sempre dal padre
                $riga['Nordine'] = $ordine->Nordine;

                // facoltativo: default
                $riga['Qta'] = $riga['Qta'] ?? 1;
                $riga['PrezzoCad'] = $riga['PrezzoCad'] ?? 0;
                $riga['PrezzoMan'] = $riga['PrezzoMan'] ?? 0;

                // ⚠️ non salvare campi solo-client (uid/Id ecc.)
                unset($riga['uid'], $riga['Id'], $riga['ID']);

                TabElementiOrdine::create($riga);
            }
            return back()->with('success', '✅ Preventivo salvato (righe registrate)');
            // return redirect()
            //    ->route('ordini.edit', $ordine->ID)
            //    ->with('success', '✅ Preventivo salvato (righe registrate)');
        }

        // ✅ fallback: vecchia richiesta “singola riga” (compatibilità)
        $data = $request->validate([
            'DimL' => 'nullable|numeric',
            'DimA' => 'nullable|numeric',
            'DimSp' => 'nullable|numeric',
            'Qta' => 'nullable|numeric|min:0',
            'PrezzoCad' => 'nullable|numeric|min:0',
            'PrezzoMan' => 'nullable|numeric|min:0',
            'NoteMan' => 'nullable|string|max:255',
            'PercFile' => 'nullable|string|max:255',

            'IdModello' => 'nullable|integer',
            'IdColAnta' => 'nullable|integer',
            'IdColTelaio' => 'nullable|integer',
            'IdSoluzione' => 'nullable|integer',
            'IdManiglia' => 'nullable|integer',
            'IdApertura' => 'nullable|integer',
            'IdTipTelaio' => 'nullable|integer',
            'IdVetro' => 'nullable|integer',
            'IdColFerr' => 'nullable|integer',
            'IdSerratura' => 'nullable|integer',
            'IdImbotte' => 'nullable|integer',

            'CkTaglioObl' => 'nullable|string|max:255',
            'TxtCassMet'  => 'nullable|string|max:255',
        ]);

        $data['Nordine'] = $ordine->Nordine;

        TabElementiOrdine::create($data);

        return redirect()
            ->route('ordini.edit', $ordine->ID)
            ->with('success', '✅ Riga preventivo aggiunta');
    }

    public function edit(TabOrdine $ordine, TabElementiOrdine $elemento)
    {
        abort_if($ordine->user_id !== Auth::id(), 403);

        abort_if($elemento->Nordine !== $ordine->Nordine, 404);

        $elementi = TabElementiOrdine::query()
            ->where('Nordine', $ordine->Nordine)
            ->orderBy('Id')
            ->get();

        return Inertia::render('Preventivi/Form', [
            'ordine'   => $ordine,
            'elementi' => $elementi,   // ✅ mostro tutte le righe
            // se vuoi evidenziare una riga selezionata, lo gestiamo lato Vue
            // 'elemento' => $elemento,
        ]);
    }

    public function update(Request $request, TabOrdine $ordine, TabElementiOrdine $elemento)
    {
        abort_if($ordine->user_id !== Auth::id(), 403);

        abort_if($elemento->Nordine !== $ordine->Nordine, 404);

        $data = $request->validate([
            'DimL' => 'nullable|numeric',
            'DimA' => 'nullable|numeric',
            'DimSp' => 'nullable|numeric',
            'Qta' => 'nullable|numeric|min:0',
            'PrezzoCad' => 'nullable|numeric|min:0',
            'PrezzoMan' => 'nullable|numeric|min:0',
            'NoteMan' => 'nullable|string|max:255',
            'PercFile' => 'nullable|string|max:255',

            'IdModello' => 'nullable|integer',
            'IdColAnta' => 'nullable|integer',
            'IdColTelaio' => 'nullable|integer',
            'IdSoluzione' => 'nullable|integer',
            'IdManiglia' => 'nullable|integer',
            'IdApertura' => 'nullable|integer',
            'IdTipTelaio' => 'nullable|integer',
            'IdVetro' => 'nullable|integer',
            'IdColFerr' => 'nullable|integer',
            'IdSerratura' => 'nullable|integer',
            'IdImbotte' => 'nullable|integer',

            'CkTaglioObl' => 'nullable|string|max:255',
            'TxtCassMet'  => 'nullable|string|max:255',
        ]);

        // Nordine non si cambia
        $elemento->update($data);

        return redirect()
            ->route('ordini.edit', $ordine->ID)
             ->with('success', '✅ Riga preventivo aggiornata');
    }

    public function destroy(TabOrdine $ordine, TabElementiOrdine $elemento)
    {
abort_if($ordine->user_id !== Auth::id(), 403);
        abort_if($elemento->Nordine !== $ordine->Nordine, 404);

        // 🔒 Sicurezza: l'elemento deve appartenere all'ordine
        if ((int) $elemento->Nordine !== (int) $ordine->Nordine) {
            abort(404, 'Elemento non appartenente all’ordine');
        }

        DB::transaction(function () use ($elemento) {
            $elemento->delete();
        });

        // ✅ Risposta Inertia-friendly
        return back()->with('success', 'Riga eliminata con successo');
    }
}

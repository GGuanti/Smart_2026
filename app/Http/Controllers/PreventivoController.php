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


class PreventivoController extends Controller
{

    public function create(TabOrdine $ordine)
    {
        abort_if($ordine->user_id !== auth()->id(), 403);

        $elementi = TabElementiOrdine::query()
            ->where('Nordine', $ordine->Nordine)
            ->orderBy('Id') // se la PK è diversa, cambiala (es. ID)
            ->get();
            $modelli = Listino::query()
            ->select(['id_listino', 'nome_modello'])
            ->orderBy('nome_modello')
            ->get();


        return Inertia::render('Preventivi/Form', [
            'ordine'   => $ordine,
            'elementi' => $elementi, // ✅ tutte le righe già presenti
            // ✅ MODELLI DA LISTINI
            'modelli' => $modelli,


            'colAnta' => FinituraAnta::orderBy('Tipologia')
                ->orderBy('Colore')
                ->get(['IdFinAnta', 'Tipologia', 'Colore']),
            'colTelaio' => FinituraTelaio::orderBy('Tipologia')
                ->orderBy('Colore')
                ->get(['IdFinTelaio', 'Tipologia', 'Colore']),


        ]);
    }

    public function store(Request $request, TabOrdine $ordine)
    {
        abort_if($ordine->user_id !== auth()->id(), 403);

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

            return redirect()
                ->route('ordini.edit', $ordine->ID)
                ->with('success', '✅ Preventivo salvato (righe registrate)');
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
        abort_if($ordine->user_id !== auth()->id(), 403);
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
        abort_if($ordine->user_id !== auth()->id(), 403);
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
        abort_if($ordine->user_id !== auth()->id(), 403);
        abort_if($elemento->Nordine !== $ordine->Nordine, 404);

        $elemento->delete();

        return redirect()
            ->route('ordini.edit', $ordine->ID)
            ->with('success', '🗑️ Riga preventivo eliminata');
    }
}

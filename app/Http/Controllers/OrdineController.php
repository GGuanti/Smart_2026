<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\TabOrdine;
use function Laravel\Prompts\alert;

class OrdineController extends Controller
{
    // app/Http/Controllers/OrdineController.php

    public function copia($id)
    {
        $old = TabOrdine::findOrFail($id);
        abort_if($old->user_id !== auth()->id(), 403);

        return DB::transaction(function () use ($old) {

            // 1) nuovo Nordine (esempio: max+1)
            $newNordine = (int) DB::table('tab_ordine')->max('Nordine') + 1;

            // 2) crea nuova testata copiando campi (escludi ID/Nordine/date conferma ecc)
            $new = $old->replicate([
                'ID',        // chiave
                'Nordine',   // numero ordine
                'DataConferma',
            ]);

            $new->Nordine = $newNordine;
            $new->user_id = auth()->id(); // sicurezza

            // opzionale: reset campi “stato”
            $new->TipoDoc = 'Preventivo';
            $new->DataConferma = null;

            $new->save();

            // 3) copia righe collegate via Nordine
            $rows = DB::table('tab_elementi_ordine')
                ->where('Nordine', $old->Nordine)
                ->get();

            foreach ($rows as $r) {
                $arr = (array) $r;

                // se la tabella righe ha id autoincrement, toglilo
                unset($arr['id']);
                unset($arr['ID']);
                unset($arr['Id']); // nel dubbio

                // assegna al nuovo Nordine
                $arr['Nordine'] = $newNordine;

                // timestamps se esistono
                if (array_key_exists('created_at', $arr)) $arr['created_at'] = now();
                if (array_key_exists('updated_at', $arr)) $arr['updated_at'] = now();

                DB::table('tab_elementi_ordine')->insert($arr);
            }

            // 4) torna all’edit del nuovo ordine
            return redirect()->route('ordini.edit', $new->ID)
                ->with('success', "Ordine copiato in Nordine {$newNordine}");
        });
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $stato = $request->string('stato')->toString(); // ✅ nuovo

        $ordini = TabOrdine::query()
            ->where('user_id', auth()->id())
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('Nordine', 'like', "%{$q}%")
                        ->orWhere('CognomeNome', 'like', "%{$q}%")
                        ->orWhere('Telefono', 'like', "%{$q}%")
                        ->orWhere('Cellulare', 'like', "%{$q}%")
                        ->orWhere('IdCitta', 'like', "%{$q}%");
                });
            })
            ->when($stato, function ($query) use ($stato) {
                $query->where('TipoDoc', $stato); // ✅ filtro stato
            })
            ->orderByDesc('Nordine')
            ->paginate(15)
            ->withQueryString();

        return inertia('Ordini/Index', [
            'ordini' => $ordini,
            'filters' => [
                'q' => $q,
                'stato' => $stato, // ✅ rimanda al frontend
            ],
        ]);
    }
    public function create()
    {
        $nextNordine = (int) (Ordine::max('Nordine') ?? 0) + 1;
        $Trasp = DB::table('tab_trasporto')
            ->select('id', 'des')
            ->orderBy('des')
            ->get();
        $ivaList = DB::table('tab_iva')
            ->select('id', 'des', 'valore', 'cod_iva')
            ->orderBy('valore')
            ->get();
        return Inertia::render('Ordini/Form', [
            'ordine'      => null,
            'elementi'    => [],
            'nextNordine' => $nextNordine,
            'mode'        => 'create',
            'ivaList' => $ivaList,
            'trasportiList' => $Trasp,
            'regioneUtente' => (string) (auth()->user()->trasporto ?? ''),
            'tariffeTrasporto' => DB::table('tab_costo_trasporto')
                ->select('regione', 'costo', 'min_tass')
                ->get(),
        ]);
    }
    public function edit($id)
    {
        $ordine = TabOrdine::with('righe')->findOrFail($id);

       // abort_if($ordine->user_id !== auth()->id(), 403);

        // 2️⃣ QUI VA IL TOTALE RIGHE 👇
        $QtaTotRighe = (float) DB::table('tab_elementi_ordine')
            ->where('Nordine', $ordine->Nordine)
            ->sum('qta');

        $Trasp = DB::table('tab_trasporto')
            ->select('id', 'des')
            ->orderBy('des')
            ->get();
        $ivaList = DB::table('tab_iva')
            ->select('id', 'des', 'valore', 'cod_iva')
            ->orderBy('valore')
            ->get();
        return Inertia::render('Ordini/Form', [
            'ordine'   => $ordine,
            'elementi' => $ordine->righe->values(),
            'mode'     => 'edit',
            'nextNordine' => null,
            'ivaList' => $ivaList,
            'trasportiList' => $Trasp,
            'QtaTotRighe' => (float)$QtaTotRighe,
            'regioneUtente' => (string) (auth()->user()->trasporto ?? ''),
            'tariffeTrasporto' => DB::table('tab_costo_trasporto')
                ->select('regione', 'costo', 'min_tass')
                ->get(),
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'CognomeNome' => 'nullable|string|max:50',
            'Telefono'    => 'nullable|string|max:25',
            'Cellulare'   => 'nullable|string|max:25',
            'Indirizzo'   => 'nullable|string|max:50',
            'CodFiscale' => 'nullable|string|max:255',
            'PIva' => 'nullable|string|max:255',
            'IdCitta'     => 'nullable|string|max:255',
            'Provincia'   => 'nullable|string|max:50',
            'CAP'         => 'nullable|string|max:15',
            'Email'       => 'nullable|string|max:255',
            'TipoDoc'     => 'required|string|max:255',
            'Sconto1'     => 'nullable|numeric|min:0|max:100',
            'Sconto2'     => 'nullable|numeric|min:0|max:100',
            'DataOrdine'  => 'nullable|date',
            'DataCons'    => 'nullable|date',
            'Annotazioni' => 'nullable|string',
            'IdIva' => 'nullable|integer|exists:tab_iva,id',
            'IdTrasporto' => 'nullable|integer',
            'CstTrasporto' => 'nullable|integer',

        ]);

        // default
        $data['Sconto1'] = $data['Sconto1'] ?? 0;
        $data['Sconto2'] = $data['Sconto2'] ?? 0;
        $data['DataOrdine'] = $data['DataOrdine'] ?? now();
        $data['DataCons'] = $data['DataCons'] ?? now();

        // ✅ QUESTA È LA RIGA CHE TI MANCAVA (MA AL POSTO GIUSTO)
        $data['user_id'] = auth()->id();
        if (empty($data['IdIva'])) {
            $iva22 = DB::table('tab_iva')
                ->where('valore', 22)
                ->value('id');

            $data['IdIva'] = $iva22;
        }
        $ordine = DB::transaction(function () use ($data) {
            $next = (int) (TabOrdine::lockForUpdate()->max('Nordine') ?? 0) + 1;
            $data['Nordine'] = $next;

            // ✅ MODEL GIUSTO
            return TabOrdine::create($data);
        });

        return redirect()
            ->route('ordini.edit', $ordine->ID)
            ->with('success', "Ordine creato: N° {$ordine->Nordine}");
    }
    public function update(Request $request, TabOrdine $ordini)
    {
        $data = $request->validate([
            'CognomeNome' => 'nullable|string|max:50',
            'Telefono'    => 'nullable|string|max:25',
            'Cellulare'   => 'nullable|string|max:25',
            'Indirizzo'   => 'nullable|string|max:50',
            'IdCitta'     => 'nullable|string|max:255',
            'CodFiscale' => 'nullable|string|max:255',
            'PIva' => 'nullable|string|max:255',
            'Email' => 'nullable|string|max:255',
            'Provincia'   => 'nullable|string|max:50',
            'CAP'         => 'nullable|string|max:15',
            'TipoDoc'     => 'required|string|max:255',
            'Sconto1'     => 'nullable|numeric|min:0|max:100',
            'Sconto2'     => 'nullable|numeric|min:0|max:100',
            'Annotazioni' => 'nullable|string',
            'DataOrdine'  => 'required|date',
            'DataCons'    => 'nullable|date',
            'IdIva' => 'nullable|integer|exists:tab_iva,id',
            'IdTrasporto' => 'nullable|integer',
            'CstTrasporto' => 'nullable|integer',

        ]);


        abort_if($ordini->user_id !== auth()->id(), 403);
        if (empty($data['IdIva'])) {
            $iva22 = DB::table('tab_iva')
                ->where('valore', 22)
                ->value('id');

            $data['IdIva'] = $iva22;
        }
        $ordini->update($data);

        return redirect()
            ->route('ordini.edit', $ordini->ID)
            ->with('success', 'Ordine aggiornato');
    }
    public function destroy($id)
    {
        $ordine = TabOrdine::findOrFail($id);
        abort_if($ordine->user_id !== auth()->id(), 403);

        DB::transaction(function () use ($ordine) {
            // 1) cancella tutte le righe collegate (via Nordine)
            DB::table('tab_elementi_ordine')
                ->where('Nordine', $ordine->Nordine)
                ->delete();

            // 2) cancella testata
            $ordine->delete();
        });

        return redirect()->route('ordini.index')
            ->with('success', 'Ordine e righe eliminati');
    }
}

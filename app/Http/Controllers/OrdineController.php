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
    public function index(Request $request)
{
    $q = $request->string('q')->toString();

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
        ->orderByDesc('Nordine')
        ->paginate(15)
        ->withQueryString();

    return inertia('Ordini/Index', [
        'ordini' => $ordini,
        'filters' => ['q' => $q],
    ]);
}
    public function create()
    {
        $nextNordine = (int) (Ordine::max('Nordine') ?? 0) + 1;
        $data['DataOrdine'] = $data['DataOrdine'] ?? now();

        return Inertia::render('Ordini/Form', [
            'ordine' => null,
            'nextNordine' => $nextNordine,
            'DataOrdine'=>null,
        ]);

    }
    public function edit($id)
    {

        $ordine = TabOrdine::findOrFail($id);
        abort_if($ordine->user_id !== auth()->id(), 403);
        return Inertia::render('Ordini/Edit', [
            'ordine' => $ordine,
        ]);
    }
    public function store(Request $request)
{
    $data = $request->validate([
        'CognomeNome' => 'nullable|string|max:50',
        'Telefono'    => 'nullable|string|max:25',
        'Cellulare'   => 'nullable|string|max:25',
        'Indirizzo'   => 'nullable|string|max:50',
        'IdCitta'     => 'nullable|string|max:255',
        'Provincia'   => 'nullable|string|max:50',
        'CAP'         => 'nullable|string|max:15',
        'Email'       => 'nullable|string|max:255',
        'TipoDoc'     => 'required|in:Preventivo,Ordine',
        'Sconto1'     => 'nullable|numeric|min:0|max:100',
        'Sconto2'     => 'nullable|numeric|min:0|max:100',
        'DataOrdine'  => 'nullable|date',
        'DataCons'    => 'nullable|date',
        'Annotazioni' => 'nullable|string',
    ]);

    // default
    $data['Sconto1'] = $data['Sconto1'] ?? 0;
    $data['Sconto2'] = $data['Sconto2'] ?? 0;
    $data['DataOrdine'] = $data['DataOrdine'] ?? now();
    $data['DataCons'] = $data['DataCons'] ?? now();

    // ✅ QUESTA È LA RIGA CHE TI MANCAVA (MA AL POSTO GIUSTO)
    $data['user_id'] = auth()->id();

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
    public function update(Request $request, TabOrdine $ordine)
{
    $data = $request->validate([
        'CognomeNome' => 'nullable|string|max:50',
        'Telefono'    => 'nullable|string|max:25',
        'Cellulare'   => 'nullable|string|max:25',
        'Indirizzo'   => 'nullable|string|max:50',
        'IdCitta'     => 'nullable|string|max:255',
        'Provincia'   => 'nullable|string|max:50',
        'CAP'         => 'nullable|string|max:15',
        'TipoDoc'     => 'required|in:Preventivo,Ordine',
        'Sconto1'     => 'nullable|numeric|min:0|max:100',
        'Sconto2'     => 'nullable|numeric|min:0|max:100',
        'Annotazioni' => 'nullable|string',
        'DataOrdine'  => 'required|date',
        'DataCons'    => 'nullable|date',
    ]);
    abort_if($ordine->user_id !== auth()->id(), 403);
    $ordine->update($data);

    return redirect()
        ->route('ordini.edit', $ordine->ID)
        ->with('success', 'Ordine aggiornato');
}
public function destroy(TabOrdine $ordine)
{
    abort_if($ordine->user_id !== auth()->id(), 403);
    $ordine->delete();

    return redirect()->route('ordini.index');
}
}

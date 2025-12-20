<?php

namespace App\Http\Controllers;

use App\Models\TabVisiteMediche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisiteMedicheController extends Controller
{
    // GET /visite-mediche?codCliente=XYZ
    public function index(Request $request)
{



        $codCliente  = $request->query('codCliente');


        $normCliente = $codCliente ? preg_replace('/\s+/', ' ', trim($codCliente)) : null;

        $rows = \App\Models\TabVisiteMediche::query()
            ->when($normCliente, fn($q) =>
                $q->whereRaw("REGEXP_REPLACE(CodCliente,'\\\\s+',' ') = ?", [$normCliente])
            )
            ->orderBy('Datavisita','asc')
            ->get();

        return response()->json($rows);

}

    // POST /visite-mediche
    public function store(Request $request)
{
        $data = $request->validate([
            'DataVisita'   => ['required','date'],
            'DataScadenza' => ['required','date','after_or_equal:DataVisita'],
            'CodCliente'   => ['required','string','max:7'],
            'UtenteMod'    => ['nullable','string','max:50'],
        ]);

        $data['UtenteMod']    = $data['UtenteMod'] ?? (optional(auth()->user())->name ?? 'system');
        $data['DataModifica'] = now();
        $data['CodCliente'] = isset($data['CodCliente'])
        ? preg_replace('/\s+/', ' ', trim($data['CodCliente']))
        : null;

        $row = \App\Models\TabVisiteMediche::create($data);

        return response()->json($row, 201);
}

    // PUT /visite-mediche/{id}
    public function update(Request $request, int $id)
    {
        $row = TabVisiteMediche::findOrFail($id);

        $data = $request->validate([
            'DataVisita'   => ['required', 'date'],
            'DataScadenza' => ['required', 'date', 'after_or_equal:DataVisita'],
            'CodCliente'   => ['required', 'string', 'max:7'],
            'UtenteMod'    => ['nullable', 'string', 'max:50'],
        ]);

        $data['UtenteMod']    = $data['UtenteMod'] ?? (Auth::user()->name ?? 'system');
        $data['DataModifica'] = now();

        $row->update($data);

        return response()->json($row);
    }

    // DELETE /visite-mediche/{id}
    public function destroy(int $id)
    {
        $row = TabVisiteMediche::findOrFail($id);
        $row->delete();

        return response()->json(['ok' => true]);
    }
}

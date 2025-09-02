<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContrattiController extends Controller
{
    /**
     * GET /contratti?codCliente=...
     * Ritorna l’elenco contratti (JSON), filtrando opzionalmente per CodCliente.
     */
    public function index(Request $request)
    {
        $codCliente = $request->query('codCliente');

        $q = DB::table('contratti')
            ->select([
                'IdContratti',
                'IdContratto',
                'NomeCognUser',
                'TipoContr',
                'Professione',
                'CCNL',
                'DataContratto',
                'DataInizio',
                'DataFineContratto',
                'Stato',
                'CodFiscale',
                'CodCliente',
            ])
            ->orderBy('DataInizio', 'desc');

        if ($codCliente) {
            $q->where('CodCliente', $codCliente);
        }

        return response()->json($q->get());
    }

    /**
     * POST /contratti
     * Crea un nuovo contratto.
     */
    public function store(Request $request)
    {
    // Calcola il nuovo IdContratto
    $codCliente = $request->input('CodCliente');

    // Estrai il massimo valore numerico dell'IdContratto per il cliente corrente
    $maxId = DB::table('contratti')
        ->where('CodCliente', $codCliente)
        ->selectRaw("MAX(CAST(SUBSTRING_INDEX(IdContratto, '-', 1) AS UNSIGNED)) as max_id")
        ->value('max_id');  // Restituisce solo il valore numerico
        $newIdContratto = str_pad($maxId + 1, 3, '0', STR_PAD_LEFT); // es: '003'

        $data = $request->validate([
'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|integer',
            'DataContratto'      => 'required|date',
            'DataInizio'         => 'required|date',
            'DataFineContratto'  => 'required|date|after_or_equal:DataInizio',
            'Stato'              => 'nullable|string|max:50',
            'StatoContratto'     => 'nullable|string|max:50',
            'CodFiscale'         => 'nullable|string|max:32',
            'CodCliente'         => 'required|string|max:20',
        ]);
 $data['IdContratto'] = "{$newIdContratto}-{$data['CodCliente']}"; // es: "003-C 861"


        // default opzionale
        if (!isset($data['Stato']) || $data['Stato'] === null) {
            $data['Stato'] = 'In vigore';
        }

        $id = DB::table('contratti')->insertGetId($data);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    /**
     * PUT /contratti/{id}
     * Aggiorna un contratto esistente (id = IdContratti PK).
     */
    public function update($id, Request $request)
    {
        $data = $request->validate([
            'IdContratto'        => 'nullable|string|max:50',
            'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|integer',
            'DataContratto'      => 'required|date',
            'DataInizio'         => 'required|date',
            'DataFineContratto'  => 'required|date|after_or_equal:DataInizio',
            'Stato'              => 'nullable|string|max:50',
            'StatoContratto' => 'nullable|string|max:50',
            'CodFiscale'         => 'nullable|string|max:32',
            'CodCliente'         => 'required|string|max:20',
        ]);

        $affected = DB::table('contratti')->where('IdContratti', $id)->update($data);

        return response()->json(['ok' => (bool) $affected]);
    }

    /**
     * DELETE /contratti/{id}
     */
    public function destroy($id)
    {
        $deleted = DB::table('contratti')->where('IdContratti', $id)->delete();

        return response()->json(['ok' => (bool) $deleted]);
    }
}



<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class NotaSpesaController extends Controller
{
    public function index(Request $request)
    {
        $codCliente = $request->query('codCliente');

        $query = DB::table('vistanotaspesa')
            ->select([
                'CodCliente',
                'DataDoc',
                'DataPag',
                'Coddoc',
                'CodAtt',
                'Cliente_Fornitore',
                'Causale_Banca_Note_Spese',
                'note',
                'Neg'
            ])
            ->orderBy('DataDoc');

        if (!empty($codCliente)) {
            $query->where('CodCliente', $codCliente);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'CodCliente' => 'required|string|max:10',
            'DataDoc' => 'nullable|date',
            'DataPag' => 'nullable|date',
            'Coddoc' => 'nullable|string|max:50',
            'CodAtt' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:255',
            'Neg' => 'nullable|numeric',
        ]);

        DB::table('nota_spese')->insert($data);

        return response()->json(['message' => 'Inserita con successo'], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'CodCliente' => 'required|string|max:10',
            'DataDoc' => 'nullable|date',
            'DataPag' => 'nullable|date',
            'Coddoc' => 'nullable|string|max:50',
            'CodAtt' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:255',
            'Neg' => 'nullable|numeric',
        ]);

        DB::table('nota_spese')->where('IDNotaSpese', $id)->update($data);

        return response()->json(['message' => 'Aggiornata con successo']);
    }

    public function destroy($id)
    {
        DB::table('nota_spese')->where('IDNotaSpese', $id)->delete();

        return response()->json(['message' => 'Eliminata con successo']);
    }
}

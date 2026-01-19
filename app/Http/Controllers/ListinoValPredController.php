<?php

namespace App\Http\Controllers;

use App\Models\ListinoValPred;
use Illuminate\Http\Request;

class ListinoValPredController extends Controller
{
    public function show(Request $request, $listino)
    {
        $idSoluzione = (int) $request->query('id_tab_soluzioni');
        $idColTelaio = (int) $request->query('id_col_telaio');
        $row = ListinoValPred::query()
            ->where('id_listino', (int) $listino)
            ->where('id_tab_soluzioni', $idSoluzione)
            ->where('id_col_telaio', $idColTelaio)
            ->first();

        return response()->json([
            'valpred' => $row?->valpred,
        ]);
    }

    public function store(Request $request, $listino)
    {
        $data = $request->validate([
            'id_tab_soluzioni' => ['required', 'integer'],
            'id_col_telaio'    => ['required', 'integer'],
            'valpred' => ['required', 'array'],
        ]);

        ListinoValPred::updateOrCreate(
            [
                'id_listino' => (int) $listino,
                'id_tab_soluzioni' => (int) $data['id_tab_soluzioni'],
                'id_col_telaio'    => (int) $data['id_col_telaio'],
            ],
            [
                'valpred' => $data['valpred'],
            ]
        );

        return back()->with('success', 'Valori predefiniti salvati');
    }


}

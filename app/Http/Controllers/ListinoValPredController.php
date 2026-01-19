<?php

namespace App\Http\Controllers;

use App\Models\ListinoValPred;
use Illuminate\Http\Request;

class ListinoValPredController extends Controller
{
    public function show(Request $request, $listino)
    {
        $idSoluzione = (int) $request->query('id_tab_soluzioni');

        $row = ListinoValPred::query()
            ->where('id_listino', (int) $listino)
            ->where('id_tab_soluzioni', $idSoluzione)
            ->first();

        return response()->json([
            'valpred' => $row?->valpred,
        ]);
    }

    public function store(Request $request, $listino)
    {
        $data = $request->validate([
            'id_tab_soluzioni' => ['required', 'integer'],
            'valpred' => ['required', 'array'],
        ]);

        ListinoValPred::updateOrCreate(
            [
                'id_listino' => (int) $listino,
                'id_tab_soluzioni' => (int) $data['id_tab_soluzioni'],
            ],
            [
                'valpred' => $data['valpred'],
            ]
        );

        return back()->with('success', 'Valori predefiniti salvati');
    }


}

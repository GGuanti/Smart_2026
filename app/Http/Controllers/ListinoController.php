<?php

namespace App\Http\Controllers;

use App\Models\Listino;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListinoController extends Controller
{
    public function create()
{
    return Inertia::render('Listini/Form', [
        'listino' => null,
        'modelli' => Listino::query()
            ->whereNotNull('nome_modello')
            ->where('nome_modello', '!=', '')
            ->distinct()
            ->orderBy('nome_modello')
            ->pluck('nome_modello')
            ->values(),

        // ✅ Mappa: { "MODELLO1": 123, "MODELLO2": 456, ... }
        'prezziBT' => Listino::query()
            ->whereNotNull('nome_modello')
            ->where('nome_modello', '!=', '')
            ->pluck('bt', 'nome_modello'),
    ]);
}


public function edit(Listino $listino)
{
    return Inertia::render('Listini/Form', [
        'listino' => $listino,
        'modelli' => Listino::query()
            ->whereNotNull('nome_modello')
            ->where('nome_modello', '!=', '')
            ->distinct()
            ->orderBy('nome_modello')
            ->pluck('nome_modello')
            ->values(),

        'prezziBT' => Listino::query()
            ->whereNotNull('nome_modello')
            ->where('nome_modello', '!=', '')
            ->pluck('bt', 'nome_modello'),
    ]);
}

    public function store(Request $request)
    {
        Listino::create($request->all());

        return redirect()->route('listini.index')
            ->with('success', 'Listino creato');
    }

    public function update(Request $request, Listino $listino)
    {
        $listino->update($request->all());

        return redirect()->route('listini.index')
            ->with('success', 'Listino aggiornato');
    }
}

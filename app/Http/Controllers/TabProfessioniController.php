<?php

// app/Http/Controllers/TabProfessioniController.php
namespace App\Http\Controllers;

use App\Models\TabProfessione;
use Illuminate\Http\Request;

class TabProfessioniController extends Controller
{
    public function index() {
        return response()->json(TabProfessione::orderBy('Professione')->get());
    }

    public function store(Request $r) {
        $data = $r->validate([
            'Professione'  => 'required|string|max:150',
            'UniLav'       => 'nullable|string|max:100',
            'LivelloCCNL'  => 'nullable|string|max:50',
            'Minima'       => 'nullable|numeric',
            'CodUniLav'    => 'nullable|string|max:50',
            'Settore'      => 'nullable|string|max:150',
        ]);
        $row = TabProfessione::create($data);
        return response()->json($row, 201);
    }

    public function update(Request $r, TabProfessione $tab_professioni) {
        $data = $r->validate([
            'Professione'  => 'required|string|max:150',
            'UniLav'       => 'nullable|string|max:100',
            'LivelloCCNL'  => 'nullable|string|max:50',
            'Minima'       => 'nullable|numeric',
            'CodUniLav'    => 'nullable|string|max:50',
            'Settore'      => 'nullable|string|max:150',
        ]);
        $tab_professioni->update($data);
        return response()->json($tab_professioni);
    }

    public function destroy(TabProfessione $tab_professioni) {
        $tab_professioni->delete();
        return response()->noContent();
    }
}

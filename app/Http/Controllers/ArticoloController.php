<?php

namespace App\Http\Controllers;

use App\Models\Articolo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ArticoloController extends Controller
{
    public function index()
    {
        return Inertia::render('Articoli/Index', [
            'articoli' => Articolo::all()
        ]);
    }

    /**
     * Esporta articoli filtrati in Excel.
     */
    public function export(Request $request)
    {
        $query = Articolo::query();

        // Applica filtri dinamici passati da Vue
        foreach ($request->input('filters', []) as $filter) {
            $field = $filter['field'] ?? null;
            $type = $filter['type'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($field && $value !== null) {
                if ($type === 'like') {
                    $query->where($field, 'LIKE', "%$value%");
                } else {
                    $query->where($field, $type, $value);
                }
            }
        }

        // Recupera dati filtrati
        $articoli = $query->select('id', 'nome', 'descrizione', 'prezzo')->get();

        // Esporta con intestazioni
        return Excel::download(
            new ArrayExport($articoli->toArray(), ['ID', 'Nome', 'Descrizione', 'Prezzo']),
            'articoli-filtrati.xlsx'
        );
    } // ✅ GRAFFA CHIUSURA MANCANTE

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'prezzo' => 'required|numeric'
        ]);

        Articolo::create($data);

        return redirect()->back()->with('success', 'Articolo creato');
    }

    public function update(Request $request, Articolo $articolo)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'prezzo' => 'required|numeric'
        ]);

        $articolo->update($data);

        return redirect()->back()->with('success', 'Articolo aggiornato');
    }

    public function destroy(Articolo $articolo)
    {
        $articolo->delete();

        return redirect()->back()->with('success', 'Articolo eliminato');
    }

    public function exportPdf(Request $request)
{
    $query = Articolo::query();

    foreach ($request->input('filters', []) as $filter) {
        $field = $filter['field'] ?? null;
        $type = $filter['type'] ?? '=';
        $value = $filter['value'] ?? null;

        if ($field && $value !== null) {
            if ($type === 'like') {
                $query->where($field, 'LIKE', "%$value%");
            } else {
                $query->where($field, $type, $value);
            }
        }
    }

    $articoli = $query->select('id', 'nome', 'descrizione', 'prezzo')->get();

    $pdf = Pdf::loadView('articoli.export', compact('articoli'));

    return $pdf->download('articoli-filtrati.pdf');
}
}

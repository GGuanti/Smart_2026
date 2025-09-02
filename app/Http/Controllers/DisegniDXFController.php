<?php

namespace App\Http\Controllers;

use App\Models\DisegnoDXF;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisegniDXFController extends Controller
{
    // Mostra la pagina: se $id c'è, carica il record e passa il DXF alla vista
    public function show(?int $id = null)
    {
        $record = null;
        if ($id) {
            $record = DisegnoDXF::findOrFail($id);
        }

        return Inertia::render('Disegni/DxfTest', [
            'record'  => $record ? [
                'IdRigaDXF'  => $record->IdRigaDXF,
                'Codice'     => $record->Codice,
                'Descrizione'=> $record->Descrizione,
                'LRG'        => $record->LRG,
                'ALT'        => $record->ALT,
            ] : null,
            'dxfText' => $record?->Dxf ?? '',
            'title'   => 'DXF Viewer (sfondo "retino" + "maniglia")'
        ]);
    }

    // Inserimento nuovo (da testo DXF o da file .dxf)
    public function store(Request $request)
    {
        $data = $request->validate([
            'Codice'      => 'nullable|string|max:64',
            'Descrizione' => 'nullable|string|max:255',
            'LRG'         => 'nullable|string|max:32',
            'ALT'         => 'nullable|string|max:32',
            'Dxf'         => 'nullable|string',  // se invii il contenuto come testo
            'dxf_file'    => 'nullable|file|mimetypes:text/plain,text/x-dxf,application/octet-stream',
        ]);

        // Se arriva un file .dxf, preferisci quello
        if ($request->hasFile('dxf_file')) {
            $data['Dxf'] = file_get_contents($request->file('dxf_file')->getRealPath());
        }

        if (empty($data['Dxf'])) {
            return back()->withErrors(['Dxf' => 'Carica un file DXF o incolla il contenuto.']);
        }

        $rec = DisegnoDXF::create($data);

        return redirect()->route('disegni.show', $rec->IdRigaDXF)
            ->with('success', 'Disegno creato.');
    }

    // Update esistente
    public function update(Request $request, int $id)
    {
        $rec = DisegnoDXF::findOrFail($id);

        $data = $request->validate([
            'Codice'      => 'nullable|string|max:64',
            'Descrizione' => 'nullable|string|max:255',
            'LRG'         => 'nullable|string|max:32',
            'ALT'         => 'nullable|string|max:32',
            'Dxf'         => 'nullable|string',
            'dxf_file'    => 'nullable|file|mimetypes:text/plain,text/x-dxf,application/octet-stream',
        ]);

        if ($request->hasFile('dxf_file')) {
            $data['Dxf'] = file_get_contents($request->file('dxf_file')->getRealPath());
        }

        if (!empty($data['Dxf'])) {
            $rec->Dxf = $data['Dxf'];
        }
        $rec->Codice      = $data['Codice']      ?? $rec->Codice;
        $rec->Descrizione = $data['Descrizione'] ?? $rec->Descrizione;
        $rec->LRG         = $data['LRG']         ?? $rec->LRG;
        $rec->ALT         = $data['ALT']         ?? $rec->ALT;
        $rec->save();

        return redirect()->route('disegni.show', $rec->IdRigaDXF)
            ->with('success', 'Disegno aggiornato.');
    }

    // API leggera che restituisce solo il testo DXF (per usi esterni)
    public function apiShow(int $id)
    {
        $rec = DisegnoDXF::findOrFail($id);
        return response()->json([
            'IdRigaDXF' => $rec->IdRigaDXF,
            'Dxf'       => $rec->Dxf,
        ]);
    }
}

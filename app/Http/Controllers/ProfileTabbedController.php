<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileTabbedController extends Controller
{
    public function index()
    {
        return Inertia::render('Esempio/TabbedForms', [
            // qui in futuro puoi passare dati iniziali
        ]);
    }
    public function storeBase(Request $request)
{
    $data = $request->validate([
        'nome' => 'required|string|max:100',
        'cognome' => 'required|string|max:100',
        'email' => 'nullable|email',
        'telefono' => 'nullable|string',
        'data_nascita' => 'nullable|date',
        'eta' => 'nullable|integer|min:0',
        'genere' => 'nullable|string',
        'password' => 'nullable|confirmed|min:8',
        'accetto_termini' => 'boolean',
        'newsletter' => 'in:si,no',
        // aggiungi gli altri campi quando serve
    ]);

    // Salvataggio (demo)
    // User::update($data);

    return back()->with('success', 'Dati base salvati');
}
public function storeAvanzati(Request $request)
{
    $data = $request->validate([
        'azione' => 'nullable|string',
        'ricerca_cliente' => 'nullable|string',
        'comune' => 'nullable|string',
        'cap' => 'nullable|string|max:5',
        'provincia' => 'nullable|string|max:2',
        'nazione' => 'nullable|string',
        'priorita' => 'integer|min:0|max:100',
        'sconto' => 'numeric|min:0|max:50',
        'budget' => 'nullable|numeric',
        'interessi' => 'array',
        'tipo_cliente' => 'in:Privato,Azienda,PA',
        'descrizione' => 'nullable|string|max:200',
    ]);

    // Salvataggio (demo)
    return back()->with('success', 'Dati avanzati salvati');
}
}

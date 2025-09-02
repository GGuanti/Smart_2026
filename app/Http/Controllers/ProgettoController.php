<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ProgettoController extends Controller
{
    public function index(Request $request)
    {
        $codCliente = $request->input('cod');

        // Progetti associati al cliente
        $query = DB::table('progetti')
            ->join('attivita', 'attivita.IDAttivita', '=', 'progetti.IdAttivita')
            ->select([
                'progetti.DataContratto as DataContratto',
                'progetti.DescrizioneProgetto as DescrizioneProgetto',
                'progetti.Accordi as Accordi',
                'progetti.ImportoNettoConcordato as ImportoNettoConcordato',
                'progetti.IdProgetto as IdProgetto',
                'progetti.IdAttivita as IdAttivita',
                'progetti.IdProg as IdProg',
                'progetti.CodCliente as CodCliente',
                'progetti.RagioneSocialeCommittenti as RagioneSocialeCommittenti',
                'progetti.TipologiaSimulatore',
                'progetti.CodCapoProgetto as CodCapoProgetto',
            ])
            ->orderBy('progetti.IdAttivita');

        if (!empty($codCliente)) {
            $query->where('progetti.CodCapoProgetto', $codCliente);
        }

        $progetti = $query->get();

        // Progetti non ancora assegnati (disponibili)
        $progettiDisponibili = DB::table('progetti')
            ->whereNull('CodCliente')
            ->select('IdProg', 'DescrizioneProgetto')
            ->orderBy('IdProg')
            ->get();

        return response()->json([
            'progetti' => $progetti,
            'progettiDisponibili' => $progettiDisponibili,
        ]);

        $codCliente = $request->input('cod');
        logger("Richiesta progetti per cliente: $codCliente");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // chiavi di collegamento
            'IdAttivitA'                 => ['required'],                       // se è int: ['required','integer','exists:attivita,IDAttivita']
            'IdProgetto'                 => ['nullable', 'string', 'max:50'],     // se AI nella tabella, verrà ignorato nell'insert
            'CodCliente'                 => ['nullable', 'string', 'max:50'],

            // anagrafica/descrizione
            'RagioneSocialeCommittenti'  => ['nullable', 'string', 'max:255'],
            'DescrizioneProgetto'        => ['nullable','string','max:2000'],
            'TipologiaSimulatore'        => ['nullable', 'string', 'max:255'],

            // date principali
            'DataInzProgetto'            => ['nullable', 'date'],
            'DataFineProgetto'           => ['nullable', 'date', 'after_or_equal:DataInzProgetto'],
            'DataPagamento'              => ['nullable', 'date'],

            // accordi/economica
            'Accordi'                    => ['nullable', 'string', 'max:1000'],
            'Percentuale'                => ['nullable', 'numeric', 'min:0', 'max:100'],
            'GGPag'                      => ['nullable', 'integer', 'min:0', 'max:365'],
            'DataEmissionePrevFattura'   => ['nullable', 'date'],
            'ImportoNettoConcordato'     => ['nullable', 'numeric', 'min:0'],
            'AliquotaIVA'                => ['nullable', 'numeric', 'min:0', 'max:100'],
            'EsenzioneIva'               => ['nullable', 'string', 'max:255'],

            // identificativi extra
            'CIG'                        => ['nullable', 'string', 'max:50'],
            'coproduzione'               => ['nullable', 'string', 'max:255'],
            'DescrCausaleFattura'        => ['nullable', 'string', 'max:1000'],

            // contatti
            'IndirizzoEmailFattura'      => ['nullable', 'string', 'max:255'],
            'IndirizzoEmailContatto'     => ['nullable', 'string', 'max:255'],

            // stato/altro
            'StatoProgetto'              => ['nullable', 'string', 'max:100'],
            'Consigliere'                => ['nullable', 'string', 'max:100'],
            'ImportGiornate'             => ['nullable', 'string', 'max:10'],

            // logistica
            'Pranzo'                     => ['nullable', 'string', 'max:255'],
            'Cena'                       => ['nullable', 'string', 'max:255'],
            'Alloggio'                   => ['nullable', 'string', 'max:255'],
            'NNotti'                     => ['nullable', 'integer', 'min:0', 'max:365'],
            'Viaggio'                    => ['nullable', 'string', 'max:255'],

            // CUP/ordine
            'CUP'                        => ['nullable', 'string', 'max:50'],
            'NOrdineCup'                 => ['nullable', 'string', 'max:100'],
            'DtOrdineCup'                => ['nullable', 'date'],

            // note
            'Note'                       => ['nullable', 'string', 'max:4000'],

        ]);

        // normalizzazione: stringhe vuote -> null
        $data = array_map(function ($v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') return null;
            }
            return $v;
        }, $data);

        // normalizza decimali con virgola
        foreach (['Percentuale', 'AliquotaIVA', 'ImportoNettoConcordato'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null) {
                $data[$k] = (float) str_replace(',', '.', $data[$k]);
            }
        }

        // cast interi
        foreach (['GGPag', 'NNotti'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null) {
                $data[$k] = (int) $data[$k];
            }
        }

        // INSERT — se IdProgetto è autoincrement NON passarla, lasciamo al DB
        $insert = $data;
        unset($insert['IdProgetto']); // rimuovi se la PK è AI; se non lo è, commenta questa riga

        $id = DB::table('progetti')->insertGetId(array_merge($insert, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', "Progetto #{$id} creato.");
    }

    public function update(Request $request, $id)
{
    // Se l'ID arriva url-encoded (es. 4858%2F2) lo decodifichiamo
    $id = urldecode($id);

    // --- PRECHECK: esiste la riga?
    $exists = DB::table('progetti')->where('IdProg', $id)->exists();


    // ✅ Se non esiste, esci subito (spiega cosa è successo)
    if (! $exists) {
        return back()->with('error', "Progetto {$id} non trovato.");
    }

    // --- VALIDAZIONE (i tuoi rules già allineati al FE)
    $data = $request->validate([
        'IdProg'                    => ['nullable','integer'],
        'IdAttivita'                => ['nullable','integer'],
        'CodCliente'                => ['nullable','string','max:50'],
        'RagioneSocialeCommittenti' => ['nullable','string','max:255'],
        'DescrizioneProgetto'       => ['nullable','string','max:2000'],
        'TipologiaSimulatore'       => ['nullable','string','max:255'],
        'DataInzProgetto'           => ['nullable','date'],
        'DataFineProgetto'          => ['nullable','date','after_or_equal:DataInzProgetto'],
        'DataPagamento'             => ['nullable','date'],
        'Accordi'                   => ['nullable','string','max:1000'],
        'Percentuale'               => ['nullable','numeric','min:0','max:100'],
        'GGPag'                     => ['nullable','integer','min:0','max:365'],
        'DataEmissionePrevFattura'  => ['nullable','date'],
        'ImportoNettoConcordato'    => ['nullable','numeric','min:0'],
        'AliquotaIVA'               => ['nullable','numeric','min:0','max:100'],
        'EsenzioneIva'              => ['nullable','string','max:255'],
        'CIG'                       => ['nullable','string','max:50'],
        'coproduzione'              => ['nullable','string','max:255'],
        'DescrCausaleFattura'       => ['nullable','string','max:1000'],
        'IndirizzoEmailFattura'     => ['nullable','string','max:255'],
        'IndirizzoEmailContatto'    => ['nullable','string','max:255'],
        'StatoProgetto'             => ['nullable','string','max:100'],
        'Consigliere'               => ['nullable','string','max:100'],
        'ImportGiornate'            => ['nullable','string','max:10'],
        'Pranzo'                    => ['nullable','string','max:255'],
        'Cena'                      => ['nullable','string','max:255'],
        'Alloggio'                  => ['nullable','string','max:255'],
        'NNotti'                    => ['nullable','integer','min:0','max:365'],
        'Viaggio'                   => ['nullable','string','max:255'],
        'CUP'                       => ['nullable','string','max:50'],
        'NOrdineCup'                => ['nullable','string','max:100'],
        'DtOrdineCup'               => ['nullable','date'],
        'Titolo'                    => ['nullable','string','max:255'],
        'Autore'                    => ['nullable','string','max:255'],
        'RegiaCoreografia'          => ['nullable','string','max:255'],
        'NumRepliche'               => ['nullable','integer','min:0','max:100000'],
        'Note'                      => ['nullable','string','max:4000'],

    ]);

    // Stringhe vuote -> null
    $data = array_map(function ($v) {
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') return null;
        }
        return $v;
    }, $data);

    // Decimali con virgola -> punto
    foreach (['Percentuale','AliquotaIVA','ImportoNettoConcordato','ImportGiornate'] as $k) {
        if (array_key_exists($k, $data) && $data[$k] !== null) {
            $data[$k] = (float) str_replace(',', '.', $data[$k]);
        }
    }
    // Interi
    foreach (['GGPag','NNotti','NumRepliche','IDAttivita'] as $k) {
        if (array_key_exists($k, $data) && $data[$k] !== null) {
            $data[$k] = (int) $data[$k];
        }
    }

    // (Solo se nel DB i nomi colonna sono diversi, abilita queste mappe)
    // if (Schema::hasColumn('progetti', 'IdAttivitA') && array_key_exists('IDAttivita', $data)) {
    //     $data['IdAttivitA'] = $data['IDAttivita']; unset($data['IDAttivita']);
    // }
    // if (Schema::hasColumn('progetti', 'DesProgetto') && array_key_exists('DescrizioneProgetto', $data)) {
    //     $data['DesProgetto'] = $data['DescrizioneProgetto']; unset($data['DescrizioneProgetto']);
    // }

    // Aggiorna (senza updated_at se la colonna non esiste)
    $updateData = $data;
    if (Schema::hasColumn('progetti', 'updated_at')) {
        $updateData['updated_at'] = now();
    }

    $updated = DB::table('progetti')->where('IdProg', $id)->update($updateData);

    return back()->with('success', "Progetto #{$id} aggiornato con successo.");
}


    public function edit($id)
    {
        $formData = DB::table('progetti')->where('IdProgetto', $id)->first();
        // Recupera la lista clienti
        $clienti = DB::table('anagrafica')
            ->where('B_TipoU', 'C')
            ->orderBy('A_NomeVisualizzato')
            ->pluck('A_NomeVisualizzato', 'CodCliente'); // [CodCliente => Nome]



        $proj = DB::table('progetti')
            ->where('IdProgetto', $id)
            ->select([
                'IdProg',
                'IdProgetto',
                'CodCliente',
                'IDAttivita',
                'RagioneSocialeCommittenti',
                'DataInzProgetto',
                'DataFineProgetto',
                'DataPagamento',
                'Note',
                'TipologiaSimulatore'

            ])
            ->first();

        abort_unless($proj, 404);

        return Inertia::render('Progetti/Edit', [
            'project' => $proj,
            'auth' => ['user' => auth()->user()],
        ]);
    }
    public function destroy($id)
    {
        DB::table('progetti')->where('IdProg', $id)->delete();

        return response()->json(['message' => 'Progetto eliminato']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AttivitaController extends Controller
{
    public function index()
    {
        // Elenco attività con progetti figli
        $attivita = DB::table('attivita')
            ->select('IDAttivita', 'CodCliente', 'NomeAttivita', 'DesProgetto')
            ->orderBy('IDAttivita')
            ->get();

        // Raccogli gli ID delle attività per fare una sola query secondaria
        $ids = $attivita->pluck('IDAttivita')->toArray();

        $progetti = DB::table('progetto')
            ->whereIn('IdAttivitA', $ids)
            ->select(
                'IdAttivitA',
                'IdProgetto',
                DB::raw('`Ragione Sociale Committenti` as RagioneSocialeCommittenti'),
                'DataInzProgetto',
                'DataFineProgetto',
                'DataPagamento'
            )
            ->orderBy('IdAttivitA')
            ->get()
            ->groupBy('IdAttivitA');

        // Aggiungi i progetti come array figli
        $attivita = $attivita->map(function ($a) use ($progetti) {
            $a->progetti = $progetti[$a->IDAttivita] ?? [];
            return $a;
        });

        return Inertia::render('Attivita/Index', [
            'attivita' => $attivita,
        ]);
    }

    public function edit($id)
    {
        $attivita = DB::table('attivita')->where('IDAttivita', $id)->first();

        if (!$attivita) {
            abort(404);
        }

        return Inertia::render('Attivita/Edit', [
            'attivita' => $attivita,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Nota: $id è l'IdProgetto nella rotta /progetti/{id}
        $data = $request->validate([
            // chiavi di collegamento (IdAttivitA modificabile?)
            'IdAttivitA'                 => ['nullable'], // se deve poter cambiare attività, metti 'required'
            'CodCliente'                 => ['nullable','string','max:50'],

            // anagrafica/descrizione
            'RagioneSocialeCommittenti'  => ['nullable','string','max:255'],
            'DesProgetto'                => ['nullable','string','max:2000'],
            'TipologiaSimulatore'        => ['nullable','string','max:255'],

            // date principali
            'DataInzProgetto'            => ['nullable','date'],
            'DataFineProgetto'           => ['nullable','date','after_or_equal:DataInzProgetto'],
            'DataPagamento'              => ['nullable','date'],

            // accordi/economica
            'Accordi'                    => ['nullable','string','max:1000'],
            'Percentuale'                => ['nullable','numeric','min:0','max:100'],
            'GGPag'                      => ['nullable','integer','min:0','max:365'],
            'DataEmissionePrevFattura'   => ['nullable','date'],
            'ImportoNettoConcordato'     => ['nullable','numeric','min:0'],
            'AliquotaIVA'                => ['nullable','numeric','min:0','max:100'],
            'EsenzioneIva'               => ['nullable','string','max:255'],

            // identificativi extra
            'CIG'                        => ['nullable','string','max:50'],
            'coproduzione'               => ['nullable','string','max:255'],
            'DescrCausaleFattura'        => ['nullable','string','max:1000'],

            // contatti
            'IndirizzoEmailFattura'      => ['nullable','email','max:255'],
            'IndirizzoEmailContatto'     => ['nullable','email','max:255'],

            // stato/altro
            'StatoProgetto'              => ['nullable','string','max:100'],
            'Consigliere'                => ['nullable','string','max:100'],
            'ImportGiornate'             => ['nullable','numeric','min:0'],

            // logistica
            'Pranzo'                     => ['nullable','string','max:255'],
            'Cena'                       => ['nullable','string','max:255'],
            'Alloggio'                   => ['nullable','string','max:255'],
            'NNotti'                     => ['nullable','integer','min:0','max:365'],
            'Viaggio'                    => ['nullable','string','max:255'],

            // CUP/ordine
            'CUP'                        => ['nullable','string','max:50'],
            'NOrdineCup'                 => ['nullable','string','max:100'],
            'DtOrdineCup'                => ['nullable','date'],

            // note
            'Note'                       => ['nullable','string','max:4000'],
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
        foreach (['Percentuale','AliquotaIVA','ImportoNettoConcordato','ImportGiornate'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null) {
                $data[$k] = (float) str_replace(',', '.', $data[$k]);
            }
        }

        // cast interi
        foreach (['GGPag','NNotti'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null) {
                $data[$k] = (int) $data[$k];
            }
        }

        $updated = DB::table('progetti')
            ->where('IdProgetto', $id)
            ->update(array_merge($data, [
                'updated_at' => now(),
            ]));

        if ($updated === 0) {
            // non trovato o dati identici
            // puoi scegliere di abortire 404 o restare silente
            // abort(404);
        }

        return redirect()->route('attivita.index')->with('success', 'Attività aggiornata con successo');

    }


    public function create()
    {
        return Inertia::render('Attivita/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // chiavi di collegamento
            'IdAttivitA'                 => ['required'],                       // se è int: ['required','integer','exists:attivita,IDAttivita']
            'IdProgetto'                 => ['nullable','string','max:50'],     // se AI nella tabella, verrà ignorato nell'insert
            'CodCliente'                 => ['nullable','string','max:50'],

            // anagrafica/descrizione
            'RagioneSocialeCommittenti'  => ['nullable','string','max:255'],
            'DesProgetto'                => ['nullable','string','max:2000'],
            'TipologiaSimulatore'        => ['nullable','string','max:255'],

            // date principali
            'DataInzProgetto'            => ['nullable','date'],
            'DataFineProgetto'           => ['nullable','date','after_or_equal:DataInzProgetto'],
            'DataPagamento'              => ['nullable','date'],

            // accordi/economica
            'Accordi'                    => ['nullable','string','max:1000'],
            'Percentuale'                => ['nullable','numeric','min:0','max:100'],
            'GGPag'                      => ['nullable','integer','min:0','max:365'],
            'DataEmissionePrevFattura'   => ['nullable','date'],
            'ImportoNettoConcordato'     => ['nullable','numeric','min:0'],
            'AliquotaIVA'                => ['nullable','numeric','min:0','max:100'],
            'EsenzioneIva'               => ['nullable','string','max:255'],

            // identificativi extra
            'CIG'                        => ['nullable','string','max:50'],
            'coproduzione'               => ['nullable','string','max:255'],
            'DescrCausaleFattura'        => ['nullable','string','max:1000'],

            // contatti
            'IndirizzoEmailFattura'      => ['nullable','email','max:255'],
            'IndirizzoEmailContatto'     => ['nullable','email','max:255'],

            // stato/altro
            'StatoProgetto'              => ['nullable','string','max:100'],
            'Consigliere'                => ['nullable','string','max:100'],
            'ImportGiornate'             => ['nullable','numeric','min:0'],

            // logistica
            'Pranzo'                     => ['nullable','string','max:255'],
            'Cena'                       => ['nullable','string','max:255'],
            'Alloggio'                   => ['nullable','string','max:255'],
            'NNotti'                     => ['nullable','integer','min:0','max:365'],
            'Viaggio'                    => ['nullable','string','max:255'],

            // CUP/ordine
            'CUP'                        => ['nullable','string','max:50'],
            'NOrdineCup'                 => ['nullable','string','max:100'],
            'DtOrdineCup'                => ['nullable','date'],

            // note
            'Note'                       => ['nullable','string','max:4000'],
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
        foreach (['Percentuale','AliquotaIVA','ImportoNettoConcordato','ImportGiornate'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null) {
                $data[$k] = (float) str_replace(',', '.', $data[$k]);
            }
        }

        // cast interi
        foreach (['GGPag','NNotti'] as $k) {
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

       return redirect()->route('attivita.index')->with('success', 'Attività creata con successo');
    }

    public function destroy($id)
    {
        DB::table('attivita')->where('IDAttivita', $id)->delete();
        return redirect()->route('attivita.index')->with('success', 'Attività eliminata');
    }
}

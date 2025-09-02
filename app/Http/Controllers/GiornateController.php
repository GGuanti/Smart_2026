<?php

namespace App\Http\Controllers;

use App\Models\Giornata;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GiornateController extends Controller
{

    public function index(Request $request)
    {
        $codAttivita = $request->query('codAttivita'); // es. "0071/05-C  2426"
        $codCliente  = $request->query('codCliente');

        abort_if(!$codAttivita, 400, 'codAttivita mancante');

        $normCliente = $codCliente ? preg_replace('/\s+/', ' ', trim($codCliente)) : null;

        $rows = \App\Models\Giornata::query()
            ->where('CodiceAttivita', $codAttivita)
            ->when($normCliente, fn($q) =>
                $q->whereRaw("REGEXP_REPLACE(CodCliente,'\\\\s+',' ') = ?", [$normCliente])
            )
            ->orderBy('Data','asc')
            ->get();

        return response()->json($rows);
    }


    public function stampaPDF(Request $request)
    {
        $validated = $request->validate([
            'codCliente' => 'required|string',

            'data_inizio' => 'required|date',
            'data_fine' => 'required|date',
        ]);

        // 1. Recupero giornate filtrate
        $giornate = DB::table('giornate')
            ->where('CodCliente', $validated['codCliente'])
            ->whereBetween('Data', [$validated['data_inizio'], $validated['data_fine']])
            ->orderBy('Data')
            ->get();

        // 2. Dati intestatario demo (sostituibili con query)
        $intestatario = [
            'Nome' => 'Giacomo Gaggiottini',
            'CodFiscale' => 'GGGGCM84D05I155Y',
            'Cell' => '+393793003443',
            'Email' => 'luxarcana@hotmail.it',
            'TipoContratto' => 'Intermittente',
            'DataInizio' => '03/03/2016',
            'DataFine' => '31/12/2018',
        ];

        $committente = 'Studio Music Show di Gualterotti Gualtiero';
        // dd($giornate);
        // 3. Generazione PDF
        $pdf = Pdf::loadView('reports.giornate_pdf', [
            'giornate' => $giornate,
            'cliente' => $validated['codCliente'],
            'committente' => $committente,
            'intestatario' => $intestatario,
            'dal' => $validated['data_inizio'],
            'al' => $validated['data_fine'],
        ])->setPaper('a4', 'portrait');

        // 4. Salvataggio su disco
        $filename = 'report_giornate_' . str_replace(' ', '_', $validated['codCliente']) . '_' . now()->format('Ymd_His') . '.pdf';
        $path = storage_path('app/reports/' . $filename);
        $pdf->save($path);

        // 6. Restituisce l'anteprima PDF nel browser
        return $pdf->stream($filename);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'CodiceAttivita'      => 'required|string|max:100', // <-- arriva nel body
            'Data'                => 'required|date',
            'IDContratto'         => 'nullable|string|max:50',
            'Retribuzione'        => 'nullable|numeric',
            'DIARIA'              => 'sometimes|boolean',
            'CodCliente'          => 'nullable|string|max:20',
            // ...altri campi...
        ]);

        $data['CodCliente'] = isset($data['CodCliente'])
            ? preg_replace('/\s+/', ' ', trim($data['CodCliente']))
            : null;

        $row = \App\Models\Giornata::create($data);

        return response()->json($row, 201);
    }
    public function update(Request $request, int $id): JsonResponse
    {
        $row = Giornata::findOrFail($id);
        $data = $this->validateData($request);

        // se arriva CodCliente lo normalizzo
        if (array_key_exists('CodCliente', $data)) {
            $data['CodCliente'] = $this->normalizeCodCliente($data['CodCliente']);
        }
        // forza boolean per coerenza
        if (array_key_exists('DIARIA', $data)) {
            $data['DIARIA'] = (bool) $data['DIARIA'];
        }

        $row->update($data);

        return response()->json($row);
    }

    /**
     * Cancella una giornata.
     */
    public function destroy(int $id): JsonResponse
    {
        $row = Giornata::findOrFail($id);
        $row->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Validazione centralizzata. Adatta i max length ai tuoi schemi.
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'Data'                => 'nullable|date',            // Y-m-d o Y-m-d H:i:s
            'IDContratto'         => 'nullable|string|max:50',
            'Retribuzione'        => 'nullable|numeric',
            'DIARIA'              => 'sometimes|boolean',

            'Denominazione_luogo' => 'nullable|string|max:200',
            'Indirizzo'           => 'nullable|string|max:200',
            'CAP'                 => 'nullable|string|max:10',
            'Comune'              => 'nullable|string|max:120',
            'Comune_straniero'    => 'nullable|string|max:120',
            'Provincia'           => 'nullable|string|max:100',
            'Sigla'               => 'nullable|string|size:2',
            'Stato'               => 'nullable|string|max:100',

            'CodiceAttivita'      => 'prohibited',              // si prende dal path
            'CodCliente'          => 'nullable|string|max:20',
            'CodUser'             => 'nullable|string|max:50',
            'Utente'              => 'nullable|string|max:120',
            'UtenteMod'           => 'nullable|string|max:120',
            'DataModifica'        => 'nullable|date',
            'Mansione'            => 'nullable|string|max:120',
            'TipoContrSimulatore' => 'nullable|string|max:120',
        ]);
    }
    public function getByProgetto($id, Request $request)
    {
        return Giornata::where('CodiceAttivita', $id)
            ->where('CodCliente', $request->codCliente)
            ->get();
    }
    /**
     * Collassa spazi multipli e trim (es. "C   861" rimane con singoli spazi).
     */
    private function normalizeCodCliente(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        // sostituisce sequenze di spazi con un solo spazio
        $v = preg_replace('/\s+/', ' ', $v);
        return $v === '' ? null : $v;
    }

  /*  public function index(Request $request)
    {
        $codCliente = $request->input('codCliente');

        $giornate = DB::table('giornate')
            ->when($codCliente, fn ($q) => $q->where('CodCliente', $codCliente))
            ->orderByDesc('Data')
            ->get();

        return view('giornate.index', compact('giornate', 'codCliente'));
    } */
}

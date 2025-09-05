<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;

class ContrattiController extends Controller
{
    /**
     * GET /contratti?codCliente=...
     * Ritorna l’elenco contratti (JSON), filtrando opzionalmente per CodCliente.
     */
    public function index(Request $request)
    {
        $codCliente = $request->query('codCliente');

        $q = DB::table('contratti')
            ->select([
                'IdContratti',
                'IdContratto',
                'NomeCognUser',
                'TipoContr',
                'Professione',
                'CCNL',
                'DataContratto',
                'DataInizio',
                'DataFineContratto',
                'Stato',
                'StatoContratto',
                'CodFiscale',
                'CodCliente',
            ])
            ->orderBy('DataInizio', 'desc');

        if ($codCliente) {
            $q->where('CodCliente', $codCliente);
        }

        return response()->json($q->get());
    }

    /**
     * POST /contratti
     * Crea un nuovo contratto.
     */
    public function store(Request $request)
    {
        // Calcola il nuovo IdContratto progressivo per cliente
        $codCliente = $request->input('CodCliente');

        $maxId = DB::table('contratti')
            ->where('CodCliente', $codCliente)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(IdContratto, '-', 1) AS UNSIGNED)) as max_id")
            ->value('max_id');

        $maxId = $maxId ?? 0; // <-- evita null+1
        $newIdContratto = str_pad($maxId + 1, 3, '0', STR_PAD_LEFT); // es: '003'

        $data = $request->validate([
            'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|string|max:50',  // <-- string, non integer
            'DataContratto'      => 'required|date',
            'DataInizio'         => 'required|date',
            'DataFineContratto'  => 'required|date|after_or_equal:DataInizio',
            'Stato'              => 'nullable|string|max:50',
            'StatoContratto'     => 'nullable|string|max:50',
            'CodFiscale'         => 'nullable|string|max:32',
            'CodCliente'         => 'required|string|max:20',
        ]);

        $data['IdContratto'] = "{$newIdContratto}-{$data['CodCliente']}"; // es: "003-C 861"

        // default opzionale
        if (!isset($data['Stato']) || $data['Stato'] === null) {
            $data['Stato'] = 'In vigore';
        }

        $id = DB::table('contratti')->insertGetId($data);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    /**
     * PUT /contratti/{id}
     * Aggiorna un contratto esistente (id = IdContratti PK).
     */
    public function update($id, Request $request)
    {
        $data = $request->validate([
            'IdContratto'        => 'nullable|string|max:50',
            'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|string|max:50', // <-- string
            'DataContratto'      => 'required|date',
            'DataInizio'         => 'required|date',
            'DataFineContratto'  => 'required|date|after_or_equal:DataInizio',
            'Stato'              => 'nullable|string|max:50',
            'StatoContratto'     => 'nullable|string|max:50',
            'CodFiscale'         => 'nullable|string|max:32',
            'CodCliente'         => 'required|string|max:20',
        ]);

        $affected = DB::table('contratti')->where('IdContratti', $id)->update($data);

        return response()->json(['ok' => (bool) $affected]);
    }

    /**
     * DELETE /contratti/{id}
     */
    public function destroy($id)
    {
        $deleted = DB::table('contratti')->where('IdContratti', $id)->delete();
        return response()->json(['ok' => (bool) $deleted]);
    }

    /**
     * GET /contratti/{id}/report
     * Genera PDF da DOCX con Gotenberg (se disponibile) o fallback Dompdf.
     */
    public function generaPdf($id)
    {
        // 1) Recupero record
        $contratto = DB::table('contratti')
            ->where('IdContratti', $id)
            ->orWhere('IdContratto', $id)
            ->first();

        if (!$contratto) {
            abort(404, 'Contratto non trovato');
        }

        // 2) Mappa segnaposto
        $data = [
            'NOME'           => $contratto->NomeCognUser ?? '',
            'COD_FISCALE'    => $contratto->CodFiscale ?? '',
            'TIPO_CONTRATTO' => $contratto->TipoContr ?? '',
            'PROFESSIONE'    => $contratto->Professione ?? '',
            'CCNL'           => (string)($contratto->CCNL ?? ''),
            'DATA_CONTRATTO' => $this->dataIt($contratto->DataContratto ?? null),
            'DATA_INIZIO'    => $this->dataIt($contratto->DataInizio ?? null),
            'DATA_FINE'      => $this->dataIt($contratto->DataFineContratto ?? null),
            'STATO'          => $contratto->Stato ?? '',
            'COD_CLIENTE'    => $contratto->CodCliente ?? '',
        ];

        // 3) Paths
        $templatePath = resource_path('word/templates/Intermittente.docx'); // usa DOCX, non DOTX
        if (!file_exists($templatePath)) {
            abort(500, 'Template contratto non trovato');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $stamp  = time();
        $docxOut = "{$tmpDir}/contratto-{$id}-{$stamp}.docx";
        $pdfOut  = "{$tmpDir}/contratto-{$id}-{$stamp}.pdf";

        // 4) Compila DOCX
        $tp = new TemplateProcessor($templatePath);
        foreach ($data as $k => $v) {
            $tp->setValue($k, $v);
        }
        $tp->saveAs($docxOut);

        // 5) Primo tentativo: Gotenberg (se configurato)
        $gotenberg = rtrim(env('GOTENBERG_URL', ''), '/');
        if ($gotenberg !== '') {
            try {
                $req = Http::timeout((int)env('GOTENBERG_TIMEOUT', 60))
                    ->connectTimeout(10)
                    ->asMultipart()
                    ->attach('files', fopen($docxOut, 'r'), 'contratto.docx');

                if (env('GOTENBERG_USER') && env('GOTENBERG_PASS')) {
                    $req = $req->withBasicAuth(env('GOTENBERG_USER'), env('GOTENBERG_PASS'));
                }
                if ($key = env('GOTENBERG_API_KEY')) {
                    $req = $req->withHeaders(['X-Api-Key' => $key]);
                }

                $resp = $req->post("{$gotenberg}/forms/libreoffice/convert", [
                    'output' => 'pdf',
                ]);

                if ($resp->ok()) {
                    file_put_contents($pdfOut, $resp->body());
                    return response()->file($pdfOut, [
                        'Content-Type' => 'application/pdf',
                    ])->deleteFileAfterSend(true);
                }

                \Log::error('Gotenberg error', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
            } catch (\Throwable $e) {
                \Log::error('Gotenberg exception', ['msg' => $e->getMessage()]);
            }
        }

        // 6) Fallback: Dompdf (meno fedele, ma senza servizi esterni)
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord   = IOFactory::load($docxOut);
        $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($pdfOut);

        return response()->file($pdfOut, ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Helper interno: formatta date (Y-m-d / string / DateTime) in d/m/Y
     */
    private function dataIt($v): string
    {
        if (!$v) return '';
        try {
            if ($v instanceof \DateTimeInterface) {
                return $v->format('d/m/Y');
            }
            $d = new \DateTime($v);
            return $d->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

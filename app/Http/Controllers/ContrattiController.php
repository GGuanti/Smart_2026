<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // 👈 QUESTA RIGA È FONDAMENTALE
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class ContrattiController extends Controller
{




    public function generaPdf($id)
    {
        // 1) Recupero record contratto
        $contratto = DB::table('contratti')
            ->where('IdContratti', $id)
            ->orWhere('IdContratto', $id)
            ->first();

        if (!$contratto) {
            abort(404, 'Contratto non trovato');
        }

    // 2) Normalizzo il tipo contratto
    $tipo = strtoupper(trim($contratto->TipoContratto ?? $contratto->TipoContr ?? ''));

    // 3) Scelta view in base al tipo
    $view = match ($tipo) {
        'INTERMITTENTE' => 'pdf.contratto_intermittente',
        'CO.CO.CO'        => 'pdf.contratto_cococo',
        default         => 'pdf.contratto_intermittente', // fallback
    };


        // 2) Recupero anagrafica collegata
        $Anagcontratto = DB::table('anagrafica')
            ->where('CodCliente', $contratto->CodCliente)
            ->first();

        // 3) Preparo i dati da passare alla view
        $data = [
            'NUM_CONTRATTO'    => $contratto->IdContratto ?? '',

            // Dati dipendente
            'NOME_COMPLETO'    => $contratto->NomeCognUser ?? '',
            'LUOGO_NASCITA'    => $Anagcontratto->L_K_LuogoNascita ?? '',
            'PROV_NASCITA'     => $Anagcontratto->T_SiglaResidenza ?? '', // o campo corretto se hai sigla nascita
            'DATA_NASCITA'     => $this->dataIt($Anagcontratto->O_DataNascita ?? null),
            'INDIRIZZO'        => $Anagcontratto->P_IndirizzoResidenza ?? '',
            'CAP'              => $Anagcontratto->Q_CAP_Residenza ?? '',
            'COMUNE_RES'       => $Anagcontratto->R_ComuneResidenza ?? '',
            'PROV_RES'         => $Anagcontratto->T_SiglaResidenza ?? '',
            'STATO_RES'        => $Anagcontratto->U_StatoResidenza ?? 'ITALIA',
            'COD_FISCALE'      => $contratto->CodFiscale ?? '',

            // Dati rapporto
            'DATA_INIZIO'      => $this->dataIt($contratto->DataInizio ?? null),
            'DATA_FINE'        => $this->dataIt($contratto->DataFineContratto ?? null),
            'PROFESSIONE'      => $contratto->Professione ?? 'Informatico',
            'LIVELLO'          => $contratto->Livello ?? '2', // adatta al campo reale se esiste
            'CCNL'             => $contratto->CCNL ?? 'CCNL per artisti, tecnici, amministrativi e ausiliari dipendenti da società cooperative e imprese sociali operanti nel settore della produzione culturale e dello spettacolo',

            // Firma contratto
            'LUOGO_CONTRATTO'  => 'Milano',
            'DATA_CONTRATTO'   => $this->dataIt($contratto->DataContratto ?? '2024-12-31'),
        ];

        // 4) Genero il PDF dalla view
        $pdf = Pdf::loadView('pdf.contratto_intermittente', $data)
            ->setPaper('a4');

        $fileName = 'contratto-intermittente-' . ($contratto->IdContratto ?? $id) . '.pdf';

        // 5) Stream inline nel browser
        return $pdf->stream($fileName);
    }


    public function generaPdf_old($id)
    {
        // 1) Recupero record
        $contratto = DB::table('contratti')
            ->where('IdContratti', $id)
            ->orWhere('IdContratto', $id)
            ->first();

        $Anagcontratto = DB::table('anagrafica')
        ->where('CodCliente', $contratto->CodCliente)
        ->first();

        if (!$contratto) {
            abort(404, 'Contratto non trovato');
        }

        // 2) Dati base disponibili (chiavi "canoniche")
        $data = [
            'NOME'           => $contratto->NomeCognUser ?? '',
            'IdContratto'    => $contratto->IdContratto ?? '',
            'COD_FISCALE'    => $contratto->CodFiscale ?? '',
            'TIPO_CONTRATTO' => $contratto->TipoContr ?? '',
            'PROFESSIONE'    => $contratto->Professione ?? '',
            'CCNL'           => (string)($contratto->CCNL ?? ''),
            'DATA_CONTRATTO' => $this->dataIt($contratto->DataContratto ?? null),
            'DATA_INIZIO'    => $this->dataIt($contratto->DataInizio ?? null),
            'DATA_FINE'      => $this->dataIt($contratto->DataFineContratto ?? null),
            'STATO'          => $contratto->Stato ?? '',
            'COD_CLIENTE'    => $contratto->CodCliente ?? '',
            'TxtLuogoNascita'    => $Anagcontratto->L_K_LuogoNascita ?? '',
            'TxtDataNascitaUser' => !empty($Anagcontratto->O_DataNascita) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $Anagcontratto->O_DataNascita)->format('d/m/Y'): '',
            'TxtIndirizzoUser'    => $Anagcontratto->P_IndirizzoResidenza ?? '',

        ];

        // 3) Path
        $templatePath = resource_path('word' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Intermittente.docx');
        if (!file_exists($templatePath)) {
            abort(500, 'Template contratto non trovato');
        }

        $tmpDir = storage_path('app' . DIRECTORY_SEPARATOR . 'tmp');
        if (!is_dir($tmpDir)) {
            if (!@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
                abort(500, 'Impossibile creare la cartella temporanea: ' . $tmpDir);
            }
        }

        $stamp   = date('YmdHis');
        $base    = 'contratto-' . $id . '-' . $stamp;
        $docxOut = $tmpDir . DIRECTORY_SEPARATOR . $base . '.docx';
        $pdfOut  = $tmpDir . DIRECTORY_SEPARATOR . $base . '.pdf';

        try {
            // 4) Compila DOCX dal template, con mappatura robusta dei segnaposto
            $tp = new TemplateProcessor($templatePath);

            // Elenco segnaposto reali nel DOCX (es. ['txtCodFiscale','NOME','IdContratto', ...])
            $placeholders = $tp->getVariables();
            Log::info('Segnaposto trovati nel template', ['vars' => $placeholders]);

            // Preparo una lookup "normalizzata" delle nostre chiavi dati
            $canonMap = $this->buildNormalizedMap($data);

            // Per ogni segnaposto del DOCX cerco il valore migliore dai nostri dati
            foreach ($placeholders as $ph) {
                $value = $this->matchPlaceholderValue($ph, $canonMap);
                $tp->setValue($ph, $value);
            }

            $tp->saveAs($docxOut);
            if (!file_exists($docxOut)) {
                throw new \RuntimeException("Il file DOCX non è stato creato: $docxOut");
            }

            // 5) Carico DOCX e aggiungo intestazione + numerazione pagine
            $phpWord = IOFactory::load($docxOut);

            foreach ($phpWord->getSections() as $section) {
                $header = $section->addHeader();

                $header->addText(
                    'Contratto di Lavoro Intermittente',
                    ['bold' => true, 'size' => 7],
                    ['alignment' => Jc::CENTER]
                );

                $header->addPreserveText(
                    'Pagina {PAGE} di {NUMPAGES}',
                    ['italic' => true, 'size' => 7],
                    ['alignment' => Jc::CENTER]
                );
            }

            // 6) Conversione PDF via Dompdf
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

            $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
            $pdfWriter->save($pdfOut);

            if (!file_exists($pdfOut)) {
                throw new \RuntimeException("Il file PDF non è stato creato: $pdfOut");
            }
        } catch (\Throwable $e) {
            Log::error('Errore generaPdf:', ['ex' => $e->getMessage()]);
            if (is_file($docxOut)) @unlink($docxOut);
            abort(500, 'Errore durante la generazione del PDF: ' . $e->getMessage());
        } finally {
            // Rimuovo il DOCX intermedio
            if (is_file($docxOut)) @unlink($docxOut);
        }

        // 7) Output inline con cleanup
        return response()->file($pdfOut, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($pdfOut).'"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Normalizza una stringa: rimuove tutto tranne A-Z/0-9 e porta in maiuscolo.
     * Es. "txtCod_Fiscale" -> "TXTCODFISCALE"
     */
    private function normalizeKey(string $s): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $s));
    }

    /**
     * Costruisce una mappa normalizzata delle chiavi dati canoniche.
     * Ritorna: ['NOME' => 'Mario Rossi', ...] sia nella forma canonica sia in forma normalizzata.
     */
    private function buildNormalizedMap(array $data): array
    {
        $map = [];
        foreach ($data as $k => $v) {
            $map[$k] = $v; // originale
            $map[$this->normalizeKey($k)] = $v; // normalizzato
        }
        return $map;
    }

    /**
     * Prova a trovare il valore migliore per un segnaposto del template.
     * Strategie:
     *  - match diretto (ph così com'è)
     *  - match normalizzato
     *  - match normalizzato rimuovendo prefisso "txt"
     *  - degradazione: stringa vuota
     */
    private function matchPlaceholderValue(string $placeholder, array $canonMap): string
    {
        // 1) Nome così com'è
        if (array_key_exists($placeholder, $canonMap)) {
            return (string) $canonMap[$placeholder];
        }

        // 2) Normalizzato
        $norm = $this->normalizeKey($placeholder);
        if (array_key_exists($norm, $canonMap)) {
            return (string) $canonMap[$norm];
        }

        // 3) Rimuovo un eventuale prefisso 'txt' e rinormalizzo
        $stripTxt = preg_replace('/^txt/i', '', $placeholder);
        $norm2 = $this->normalizeKey($stripTxt);
        if (array_key_exists($norm2, $canonMap)) {
            return (string) $canonMap[$norm2];
        }

        // 4) Vuoto se non trovato
        return '';
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

    /**
     * GET /contratti?codCliente=...
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
                'CodFiscale',
                'CodCliente',
            ])
            ->orderBy('DataInizio', 'desc');

        if (!empty($codCliente)) {
            $q->where('CodCliente', $codCliente);
        }

        return response()->json($q->get());
    }

    /**
     * POST /contratti
     */
    public function store(Request $request)
    {
        // Validazione
        $data = $request->validate([
            'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|integer',
            'DataContratto'      => 'required|date',
            'DataInizio'         => 'required|date',
            'DataFineContratto'  => 'required|date|after_or_equal:DataInizio',
            'Stato'              => 'nullable|string|max:50',
            'StatoContratto'     => 'nullable|string|max:50',
            'CodFiscale'         => 'nullable|string|max:32',
            'CodCliente'         => 'required|string|max:20',
        ]);

        $codCliente = $data['CodCliente'];

        // Calcolo nuovo IdContratto progressivo per cliente (parte numerica a sinistra del "-")
        $maxId = DB::table('contratti')
            ->where('CodCliente', $codCliente)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(IdContratto, '-', 1) AS UNSIGNED)) as max_id")
            ->value('max_id');

        $progressivo = (int)($maxId ?? 0) + 1;
        $prefisso    = str_pad((string)$progressivo, 3, '0', STR_PAD_LEFT);

        $data['IdContratto'] = "{$prefisso}-{$codCliente}"; // es: "003-C 861"

        // default opzionale
        if (!isset($data['Stato']) || $data['Stato'] === null || $data['Stato'] === '') {
            $data['Stato'] = 'In vigore';
        }

        $id = DB::table('contratti')->insertGetId($data);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    /**
     * PUT /contratti/{id}
     */
    public function update($id, Request $request)
    {
        $data = $request->validate([
            'IdContratto'        => 'nullable|string|max:50',
            'NomeCognUser'       => 'required|string|max:255',
            'TipoContr'          => 'required|string|max:100',
            'Professione'        => 'required|string|max:100',
            'CCNL'               => 'nullable|integer',
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
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Appointment;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;




class AppointmentController extends Controller
{
    public function EseguiAccess(Request $request)
        {
    }
    public function ImportaDati(Request $request)
    {
        @set_time_limit(900);
        @ini_set('max_execution_time', '900');

        $report = [
            'exe' => null,
            'ordini' => null,
            'items' => null,
        ];

        try {
            // 1) RUN EXE (solo export CSV)
            $report['exe'] = $this->EstraiDatiDaAccess($request);

            // 2) IMPORT ORDINI (testate)
            $report['ordini'] = $this->ImportOrdini($request);

            // 3) IMPORT ITEMS (righe)
            $report['items'] = $this->ImportProdotti($request);

            // ✅ OK finale
            return redirect()->route('appointments.calendar')
                ->with('success', 'Import completo: EXE + Ordini + Righe eseguiti correttamente.')
                ->with('import_all_report', $report);
        } catch (\Throwable $e) {
            Log::error('IMPORT ALL FAIL', [
                'error' => $e->getMessage(),
                'report' => $report,
            ]);

            return redirect()->route('appointments.calendar')
                ->with('error', 'Import completo fallito: ' . $e->getMessage())
                ->with('import_all_report', $report);
        }
    }
    private function EstraiDatiDaAccess(Request $request): array
    {
        $accessExe64 = 'C:\Program Files\Microsoft Office\root\Office16\MSACCESS.EXE';
        $accessExe32 = 'C:\Program Files (x86)\Microsoft Office\root\Office16\MSACCESS.EXE';
        $accessExe   = file_exists($accessExe32) ? $accessExe32 : $accessExe64;

        $dbPath    = 'C:\nurith\Nurith.accdb';
        $macroName = 'mcrExportAppointmentItemsCsv';

        $csvItems  = 'C:\Nurith\Nurith.csv';
        // Se generi anche Ordini.csv dalla macro, metti anche:
        $csvOrdini = 'C:\Nurith\Ordini.csv';

        if (!file_exists($accessExe)) throw new \Exception('MSACCESS.EXE non trovato: ' . $accessExe);
        if (!file_exists($dbPath))   throw new \Exception('DB Access non trovato: ' . $dbPath);

        (new Process(['taskkill', '/F', '/IM', 'MSACCESS.EXE']))->run();

        if (!is_dir('C:\Nurith')) @mkdir('C:\Nurith', 0777, true);

        if (file_exists($csvItems)) @unlink($csvItems);
        // se vuoi rigenerare anche Ordini.csv:
        // if (file_exists($csvOrdini)) @unlink($csvOrdini);

        $process = new Process([
            'cmd', '/c',
            'start', '""',
            '/min',
            '/wait',
            $accessExe,
            $dbPath,
            '/x', $macroName,
        ]);

        $process->setWorkingDirectory('C:\nurith');
        $process->setTimeout(300);
        $process->run();

        Log::info('Access export', [
            'exit' => $process->getExitCode(),
            'err'  => $process->getErrorOutput(),
            'out'  => $process->getOutput(),
        ]);

        if (!$process->isSuccessful()) {
            throw new \Exception('Access non ha completato correttamente (ExitCode ' . $process->getExitCode() . ')');
        }

        // aspetta file Items
        if (!$this->waitForStableFile($csvItems, 30, 250)) {
            throw new \Exception('CSV items non generato (o non stabile): ' . $csvItems);
        }

        return [
            'ok' => true,
            'csv_items' => $csvItems,
            'size_items' => filesize($csvItems),
            // 'csv_ordini' => $csvOrdini,
            // 'size_ordini' => file_exists($csvOrdini) ? filesize($csvOrdini) : null,
        ];
    }

    private function ImportOrdini(Request $request): array
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        $path = 'C:\Nurith\Ordini.csv';
        $delimiter = ';';

        if (!file_exists($path)) throw new \Exception("File CSV Ordini non trovato: {$path}");
        $handle = fopen($path, 'r');
        if (!$handle) throw new \Exception("Impossibile aprire il file: {$path}");

        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeader) {
            fclose($handle);
            throw new \Exception('CSV Ordini vuoto o header mancante.');
        }

        // header normalizzato in lowercase
        $header = array_map(fn ($h) => strtolower(trim((string)$h)), $rawHeader);
        \Log::info('IMPORT Ordini.csv START', ['file' => $path, 'header' => $rawHeader]);
        \Log::info('IMPORT Ordini header normalized', ['header' => $header]);

        if (!in_array('nordine', $header, true)) {
            fclose($handle);
            throw new \Exception('Colonna obbligatoria mancante: Nordine');
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];
        $DETAIL_LIMIT = 1250;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row = array_map(function ($v) {
                    if ($v === null) return null;
                    $v = (string)$v;

                    // converte da Windows-1252 / ISO-8859-1 / UTF-8 -> UTF-8 pulito
                    $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');

                    // toglie eventuali caratteri strani invisibili
                    $v = preg_replace('/\x{FEFF}|\x{200B}|\x{00A0}/u', ' ', $v);

                    return trim($v);
                }, $row);

                if (count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) continue;
                Log::info('CSV user_id debug', [
                    'raw_user_id' => $r['user_id'] ?? null,
                    'parsed' => $this->int0($r['user_id'] ?? 0),
                    'auth' => auth()->id(),
                  ]);
                $r = [];
                foreach ($header as $i => $col) $r[$col] = $row[$i] ?? null;

                $NordineRaw = trim((string)($r['nordine'] ?? ''));
                if ($NordineRaw === '' || !ctype_digit($NordineRaw)) {
                    $errors++;
                    continue;
                }
                $Nordine = (int)$NordineRaw;

                // campi
               // $csvUserId       = (int)($this->int0($r['user_id'] ?? 0) ?: auth()->id());
               $csvUserId = auth()->id();
                $csvTitle        = $this->nullIfEmpty($r['title'] ?? null);
                $csvDescription  = $this->nullIfEmpty($r['description'] ?? null);
                $csvStatus       = $this->nullIfEmpty($r['status'] ?? null);
                $csvRiferimento  = $this->nullIfEmpty($r['riferimento'] ?? null);
                $csvColore       = $this->nullIfEmpty($r['colore'] ?? null);
                $csvAnnotazioni  = $this->nullIfEmpty($r['annotazioni'] ?? null);
                $csvStatoMag = $this->nullIfEmpty($r['statomagazzino'] ?? null);

                // date (nel tuo CSV DataConferma è dd/mm/yyyy)
                $dInizio    = $this->parseCsvDate($r['datainizio'] ?? null);
                $dFine      = $this->parseCsvDate($r['datafine'] ?? null);
                $dConferma  = $this->parseCsvDate($r['dataconferma'] ?? null);
                $dConsegna  = $this->parseCsvDate($r['dataconsegna'] ?? null);

                // pezzi (nel CSV spesso vuoto)
                $csvPezzi = $this->int0($r['pezzi'] ?? 0);

                // Prodotto è JSON tipo ["PA"]
                $prodRaw = $this->nullIfEmpty($r['prodotto'] ?? null);
                $prodArr = null;
                if ($prodRaw !== null) {
                    $try = json_decode($prodRaw, true);
                    $prodArr = is_array($try) ? $try : null; // nel tuo caso è array
                }

                $appt = Appointment::where('Nordine', $Nordine)->first();

                if ($appt) {
                    // se vuoi mantenere la regola: aggiorna solo se "Da Pianificare"
                    if ($appt->status === 'Da Pianificare') {
                        $appt->update([
                            'user_id'        => $csvUserId,
                            'title'          => $csvTitle ?? $appt->title,
                            'description'    => $csvDescription, // può diventare null se vuoto
                          //  'DataInizio'     => $dInizio ?? $appt->DataInizio,
                          //  'DataFine'       => $dFine ?? ($dInizio ?? $appt->DataFine),
                          //  'status'         => $csvStatus ?? $appt->status,
                            'Riferimento'    => $csvRiferimento,
                            'DataConferma'   => $dConferma,
                            'DataConsegna'   => $dConsegna,
                            'Colore'         => $csvColore,
                         //   'Pezzi'          => $csvPezzi,
                            'Annotazioni'    => $csvAnnotazioni,
                            'StatoMagazzino' => $csvStatoMag,
                            'Prodotto'       => $prodArr, // array
                        ]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    Appointment::create([
                        'user_id'        => $csvUserId,
                        'Nordine'        => $Nordine,
                        'title'          => $csvTitle ?? ('Ordine ' . $Nordine),
                        'description'    => $csvDescription,

                        'DataInizio'     => $dInizio,
                        'DataFine'       => $dFine ?? $dInizio,

                        'status'         => $csvStatus ?? 'Da Pianificare',
                        'Riferimento'    => $csvRiferimento,
                        'DataConferma'   => $dConferma,
                        'DataConsegna'   => $dConsegna,

                        'Colore'         => $csvColore,
                        'Pezzi'          => $csvPezzi,
                        'Annotazioni'    => $csvAnnotazioni,
                        'StatoMagazzino' => $csvStatoMag,

                        'Prodotto'       => $prodArr,
                    ]);

                    $inserted++;
                }
            }

            DB::commit();
            fclose($handle);

            return compact('inserted', 'updated', 'skipped', 'errors') + [
                'ok' => true,
                'file' => $path,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }

    private function ImportProdotti(Request $request): array
    {
        $path = 'C:\Nurith\Nurith.csv';
        $delimiter = ';';

        if (!file_exists($path)) throw new \Exception("File CSV Items non trovato: {$path}");
        $handle = fopen($path, 'r');
        if (!$handle) throw new \Exception("Impossibile aprire il file: {$path}");

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            throw new \Exception("CSV vuoto o header mancante.");
        }
        $header = array_map(fn ($h) => trim((string)$h), $header);

        if (!in_array('Nordine', $header, true)) {
            fclose($handle);
            throw new \Exception("Colonna obbligatoria mancante: Nordine");
        }

        $inserted = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];
        $DETAIL_LIMIT = 1250;
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;

                if (count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) continue;

                $data = [];
                foreach ($header as $i => $col) $data[$col] = $row[$i] ?? null;

                $NordineRaw = trim((string)($data['Nordine'] ?? ''));
                if ($NordineRaw === '' || !ctype_digit($NordineRaw)) {
                    $errors++;
                    continue;
                }
                $Nordine = (int)$NordineRaw;

                $existsInAppointments = DB::table('appointments')
                    ->where('NOrdine', $Nordine)->orWhere('Nordine', $Nordine)->exists();

                if (!$existsInAppointments) {
                    $skipped++;
                    continue;
                }

                $colore = $this->nullIfEmpty($data['Colore'] ?? null);
                // ✅ tampone (se non hai ancora allargato la colonna)
                if ($colore !== null) $colore = mb_substr($colore, 0, 100);

                $insert = [
                    'Nordine'     => $Nordine,
                    'Prodotto'    => $this->nullIfEmpty($data['Prodotto'] ?? null),
                    'Descrizione' => $this->nullIfEmpty($data['Descrizione'] ?? null),
                    'Colore'      => $colore,
                    'Pezzi'       => $this->int0($data['Pezzi'] ?? 0),
                    'Lotto'       => $this->nullIfEmpty($data['Lotto'] ?? null),
                    'Taglio'      => $this->bit($data['Taglio'] ?? 0),
                    'Assemblaggio' => $this->bit($data['Assemblaggio'] ?? 0),
                    'Comandi'     => $this->bit($data['Comandi'] ?? 0),
                    'TaglioZoccolo' => $this->bit($data['TaglioZoccolo'] ?? 0),
                    'TaglioLamelle' => $this->bit($data['TaglioLamelle'] ?? 0),
                    'MontaggioLamelle' => $this->bit($data['MontaggioLamelle'] ?? 0),
                    'Ferramenta'  => $this->bit($data['Ferramenta'] ?? 0),
                    'Vetratura'   => $this->bit($data['Vetratura'] ?? 0),
                    'Accessori'   => $this->bit($data['Accessori'] ?? 0),
                    'Coprifili'   => $this->bit($data['Coprifili'] ?? 0),
                    'Fermavetri'  => $this->bit($data['Fermavetri'] ?? 0),
                    'OrdineVetri' => $this->bit($data['OrdineVetri'] ?? 0),
                ];

                $dup = DB::table('appointment_items')
                    ->where('Nordine', $insert['Nordine'])
                    ->where('Prodotto', $insert['Prodotto'])
                    ->where('Lotto', $insert['Lotto'])
                    ->where('Descrizione', $insert['Descrizione'])
                    ->exists();

                if ($dup) {
                    $skipped++;
                    continue;
                }

                DB::table('appointment_items')->insert($insert);
                $inserted++;
            }

            DB::commit();
            fclose($handle);

            return compact('inserted', 'skipped', 'errors', 'details') + [
                'ok' => true,
                'file' => $path,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }

    // public function importAppointmentsCsv(Request $request)



    /**
     * Accetta:
     * - "2025-12-20"
     * - "20/12/2025"
     * Ritorna "Y-m-d" o null.
     */
    private function parseCsvDate($v): ?string
    {
        $s = trim((string)($v ?? ''));
        if ($s === '') return null;

        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $s)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d');
            }
            // fallback: Carbon parse (ISO ecc.)
            return \Carbon\Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable $e) {
            // se vuoi contare l’errore, fallo nel loop. Qui ritorno null “silenzioso”.
            return null;
        }
    }

    private function parseDateIt(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') return null;

        // accetta: dd/mm/yyyy oppure dd/mm/yyyy HH:ii
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}(\s+\d{2}:\d{2})?$/', $v)) {
            $fmt = str_contains($v, ' ') ? 'd/m/Y H:i' : 'd/m/Y';
            return \Carbon\Carbon::createFromFormat($fmt, $v, 'Europe/Rome')
                ->format('Y-m-d H:i:s');
        }

        // accetta: yyyy-mm-dd oppure yyyy-mm-dd HH:ii(:ss)
        if (preg_match('/^\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2}(:\d{2})?)?$/', $v)) {
            $fmt = str_contains($v, ':')
                ? (substr_count($v, ':') === 1 ? 'Y-m-d H:i' : 'Y-m-d H:i:s')
                : 'Y-m-d';

            return \Carbon\Carbon::createFromFormat($fmt, $v, 'Europe/Rome')
                ->format('Y-m-d H:i:s');
        }

        // fallback ultimo tentativo
        return \Carbon\Carbon::parse($v, 'Europe/Rome')->format('Y-m-d H:i:s');
    }
    // ---------------- helpers ----------------

    private function pushDetail(array &$details, int $limit, array $item): void
    {
        if (count($details) < $limit) {
            $details[] = $item;
        }
    }

    private function compactRow(array $data): array
    {
        // evita di salvare roba enorme in sessione
        return [
            'Nordine' => $data['Nordine'] ?? null,
            'Prodotto' => $data['Prodotto'] ?? null,
            'Lotto' => $data['Lotto'] ?? null,
            'Colore' => $data['Colore'] ?? null,
            'Pezzi' => $data['Pezzi'] ?? null,
        ];
    }

    private function bit($v): int
    {
        $s = strtolower(trim((string)$v));
        if (in_array($s, ['1', 'true', 'si', 'sì', 'yes', 'y'], true)) return 1;
        return 0;
    }

    private function int0($v): int
    {
        if ($v === null) return 0;
        $s = trim((string)$v);
        if ($s === '') return 0;
        $s = str_replace(',', '.', $s);
        return (int)floor((float)$s);
    }

    private function nullIfEmpty($v): ?string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }



    /**
     * Aspetta che un file esista e che la dimensione si stabilizzi (evita race condition).
     */
    private function waitForStableFile(string $path, int $tries = 20, int $sleepMs = 250): bool
    {
        $last = -1;
        for ($i = 0; $i < $tries; $i++) {
            if (!file_exists($path)) {
                usleep($sleepMs * 1000);
                continue;
            }
            clearstatcache(true, $path);
            $size = filesize($path);
            if ($size > 0 && $size === $last) {
                return true; // stabile
            }
            $last = $size;
            usleep($sleepMs * 1000);
        }
        return file_exists($path) && filesize($path) > 0;
    }


    // Mostra il calendario solo per l'utente loggato
    public function index()
    {
        $appointments = Appointment::with('user')
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('Appointments/Calendar', [
            'appointments' => $appointments,
        ]);
    }

    // Mostra il calendario completo (admin)
    public function calendar()
    {
        $appointments = Appointment::where('user_id', auth()->id())
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'start' => $appointment->DataInizio, // <-- qui
                    'end' => $appointment->DataFine,     // <-- qui
                    'status' => $appointment->status,
                ];
            });

        return Inertia::render('Appointments/Calendar', [
            'appointments' => $appointments,
        ]);
    }

    // Form per creare un appuntamento
    public function create(Request $request)
    {
        return Inertia::render('Appointments/Create', [
            'prefill' => [
                'DataInizio' => $request->get('DataInizio'),
                'DataConsegna' => $request->get('DataConsegna'),
                'DataConferma' => $request->get('DataConsegna'),

            ],
            // altre props...
        ]);
    }


    // Salva un nuovo appuntamento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'DataInizio' => 'required|date',
            'DataFine' => 'required|date|after_or_equal:DataInizio',

            'DataConferma' => 'required|date',
            'DataConsegna' => 'required|date|after_or_equal:DataConferma',

            // ✅ i tuoi 5 stati
            'status' => 'required|in:Da Pianificare,Pianificato,Completato,Sospeso,Cancellato',

            'StatoMagazzino' => 'required|in:Magazzino,Ordinato,Arrivato,In ritardo',

            'Nordine' => 'required|integer|min:1',
            'Riferimento' => 'nullable|string|max:255',
            'Annotazioni' => 'nullable|string|max:255',

            // ✅ RIGHE
            'items' => ['nullable', 'array'],
            'items.*.prodotto' => ['nullable', 'string', 'max:255'],
            'items.*.colore' => ['nullable', 'string', 'max:255'],
            'items.*.descrizione' => ['nullable', 'string'],
            'items.*.Lotto' => ['nullable', 'string'],
            'items.*.pezzi' => ['nullable', 'integer', 'min:0'],
            'items.*.taglio' => ['nullable', 'boolean'],
            'items.*.Accessori' => ['nullable', 'boolean'],
            'items.*.Coprifili' => ['nullable', 'boolean'],
            'items.*.Fermavetri' => ['nullable', 'boolean'],
            'items.*.assemblaggio' => ['nullable', 'boolean'],
            'items.*.comandi' => ['nullable', 'boolean'],
            'items.*.taglio_zoccolo' => ['nullable', 'boolean'],
            'items.*.taglio_lamelle' => ['nullable', 'boolean'],
            'items.*.montaggio_lamelle' => ['nullable', 'boolean'],
            'items.*.Ferramenta' => ['nullable', 'boolean'],
            'items.*.Fermavetri' => ['nullable', 'boolean'],
            'items.*.Vetratura' => ['nullable', 'boolean'],
            'items.*.OrdineVetri' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated) {

            // ✅ prodotti unici (da items)
            $prodotti = collect($validated['items'] ?? [])
                ->pluck('prodotto')
                ->filter()
                ->unique()
                ->values()
                ->all(); // es: ["IA","PA","SC"]

            // ✅ normalizza date (Y-m-d)
            $validated['DataInizio']    = Carbon::parse($validated['DataInizio'])->format('Y-m-d');
            $validated['DataFine']      = Carbon::parse($validated['DataFine'])->format('Y-m-d');
            $validated['DataConferma']  = Carbon::parse($validated['DataConferma'])->format('Y-m-d');
            $validated['DataConsegna']  = Carbon::parse($validated['DataConsegna'])->format('Y-m-d');

            // ✅ separo righe
            $items = $validated['items'] ?? [];
            unset($validated['items']);
            $validated['user_id'] = auth()->id();
            // ✅ crea testata
            $appointment = Appointment::create($validated);

            // ✅ crea righe
            $mapped = collect($items)->map(function ($it) use ($appointment) {
                return [
                    // se usi FK su Nordine (come nel tuo update)
                    'Nordine' => $appointment->Nordine,

                    'Prodotto' => $it['prodotto'] ?? null,
                    'Descrizione' => $it['descrizione'] ?? null,
                    'Lotto' => $it['Lotto'] ?? null,

                    'Colore' => $it['colore'] ?? null,
                    'Pezzi' => (int)($it['pezzi'] ?? 0),

                    'Taglio' => (bool)($it['taglio'] ?? false),
                    'Assemblaggio' => (bool)($it['assemblaggio'] ?? false),
                    'Comandi' => (bool)($it['comandi'] ?? false),

                    'TaglioZoccolo' => (bool)($it['taglio_zoccolo'] ?? false),
                    'TaglioLamelle' => (bool)($it['taglio_lamelle'] ?? false),
                    'MontaggioLamelle' => (bool)($it['montaggio_lamelle'] ?? false),

                    'Ferramenta' => (bool)($it['Ferramenta'] ?? false),
                    'Vetratura' => (bool)($it['Vetratura'] ?? false),
                    'Accessori' => (bool)($it['Accessori'] ?? false),
                    'Coprifili' => (bool)($it['Coprifili'] ?? false),
                    'Fermavetri' => (bool)($it['Fermavetri'] ?? false),
                    'OrdineVetri' => (bool)($it['OrdineVetri'] ?? false),

                ];
            })->all();

            if (!empty($mapped)) {
                $appointment->items()->createMany($mapped);
            }

            // ✅ calcolo pezzi e salvo prodotto[] sulla testata
            $sum = $appointment->items()->sum('Pezzi');

            $appointment->update([
                'Pezzi' => (int) $sum,
                'Prodotto' => $prodotti,
            ]);

            // redirect dove preferisci:
            return redirect()
                ->route('appointments.edit', $appointment->id)
                ->with('success', 'Ordine creato con successo.');
            // oppure:
            // ->route('appointments.calendar')
        });
    }

    // Visualizza un singolo appuntamento
    public function show(Appointment $appointment)
    {
        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
        ]);
    }

    // Form di modifica
    public function edit(Appointment $appointment)
    {


        $appointment->load('items'); // ✅ fondamentale


        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
        ]);
    }

    // Aggiorna un appuntamento
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'DataInizio' => 'required|date',
            'DataFine' => 'required|date|after_or_equal:DataInizio',
            'DataConferma' => 'required|date',
            'DataConsegna' => 'required|date|after_or_equal:DataConferma',
            'status' => 'required',
            'StatoMagazzino' => 'required|in:Magazzino,Ordinato,Arrivato,In ritardo',
            'Nordine' => 'required|integer|min:1',
            'Riferimento' => 'nullable|string|max:255',
            'Annotazioni' => 'nullable|string|max:255',
            // ✅ RIGHE
            'items' => ['nullable', 'array'],
            'items.*.prodotto' => ['nullable', 'string', 'max:255'],
            'items.*.colore' => ['nullable', 'string', 'max:255'],
            'items.*.descrizione' => ['nullable', 'string'],
            'items.*.Lotto' => ['nullable', 'string'],
            'items.*.pezzi' => ['nullable', 'integer', 'min:0'],
            'items.*.taglio' => ['nullable', 'boolean'],
            'items.*.assemblaggio' => ['nullable', 'boolean'],
            'items.*.comandi' => ['nullable', 'boolean'],
            'items.*.taglio_zoccolo' => ['nullable', 'boolean'],
            'items.*.taglio_lamelle' => ['nullable', 'boolean'],
            'items.*.montaggio_lamelle' => ['nullable', 'boolean'],
            'items.*.Ferramenta' => ['nullable', 'boolean'],
            'items.*.Fermavetri' => ['nullable', 'boolean'],
            'items.*.Vetratura' => ['nullable', 'boolean'],
            'items.*.Coprifili' => ['nullable', 'boolean'],
            'items.*.Accessori' => ['nullable', 'boolean'],
            'items.*.OrdineVetri' => ['nullable', 'boolean'],

        ]);
        $prodotti = collect($validated['items'] ?? [])
            ->pluck('prodotto')
            ->filter()                 // rimuove null/""
            ->unique()
            ->values()
            ->all();                   // es: ["IA","PA","SC"]

        // Normalizza datetime in formato MySQL (se ti serve uniformità)
        $validated['DataInizio'] = Carbon::parse($validated['DataInizio'])->format('Y-m-d');
        $validated['DataFine'] = Carbon::parse($validated['DataFine'])->format('Y-m-d');
        $validated['DataConferma'] = Carbon::parse($validated['DataConferma'])->format('Y-m-d');
        $validated['DataConsegna'] = Carbon::parse($validated['DataConsegna'])->format('Y-m-d');


        // ✅ separo righe
        $items = $validated['items'];
        unset($validated['items']);

        // ✅ aggiorno testata
        $appointment->update($validated);

        // ✅ riscrivo righe (semplice e robusto)
        $appointment->items()->delete();

        $mapped = collect($items)->map(function ($it) use ($appointment) {
            return [
                // se usi FK su Nordine:
                'Nordine' => $appointment->Nordine,
                'Prodotto' => $it['prodotto'],
                'Descrizione' => $it['descrizione'] ?? null,
                'Lotto' => $it['Lotto'] ?? null,

                'Colore' => $it['colore'] ?? null,
                'Pezzi' => (int)($it['pezzi'] ?? 0),
                'Taglio' => (bool)($it['taglio'] ?? false),
                'Assemblaggio' => (bool)($it['assemblaggio'] ?? false),
                'Comandi' => (bool)($it['comandi'] ?? false),
                'TaglioZoccolo' => (bool)($it['taglio_zoccolo'] ?? false),
                'TaglioLamelle' => (bool)($it['taglio_lamelle'] ?? false),
                'MontaggioLamelle' => (bool)($it['montaggio_lamelle'] ?? false),
                'Ferramenta' => (bool)($it['Ferramenta'] ?? false),
                'Fermavetri' => (bool)($it['Fermavetri'] ?? false),
                'Vetratura' => (bool)($it['Vetratura'] ?? false),
                'Coprifili' => (bool)($it['Coprifili'] ?? false),
                'Accessori' => (bool)($it['Accessori'] ?? false),
                'OrdineVetri' => (bool)($it['OrdineVetri'] ?? false),
            ];
        })->all();

        $appointment->items()->createMany($mapped);
        $sum = $appointment->items()->sum('Pezzi');

        $appointment->update([
            'Pezzi' => (int) $sum,
            'Prodotto' => $prodotti,
        ]);
        // return redirect()->route('appointments.calendar')->with('success', 'Ordine aggiornato con successo.');

        //  return redirect()->route('appointments.calendar')->with('success', 'Appuntamento aggiornato');
    }



    // Elimina un appuntamento
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.calendar')->with('success', 'Appuntamento eliminato.');
    }

    public function move(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'start'  => ['required', 'date'],
            'end'    => ['nullable', 'date'],
            'allDay' => ['required', 'boolean'],
        ]);


        if ($validated['allDay']) {
            // allDay -> giorno intero
            $start = Carbon::parse($validated['start'])
            ->setTimezone('Europe/Rome')
            ->startOfDay();

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $start->copy()->endOfDay()->toDateTimeString(),
            ]);
        } else {
            // evento con orario: end DEVE esserci
            if (empty($validated['end'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'end è obbligatorio per eventi non allDay',
                ], 422);
            }

            $start = Carbon::parse($validated['start'])->setTimezone('Europe/Rome');
            $end   = Carbon::parse($request->end)->setTimezone('Europe/Rome');

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $end->toDateTimeString(),
            ]);
        }

      //  return response()->json(['success' => true]);
    }

    // Sposta evento dal calendario (drag & drop)
    public function move1(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'start'  => ['required'],
            'allDay' => ['required', 'boolean'],
        ]);

        $tz = config('app.timezone', 'Europe/Rome');

        if ($validated['allDay']) {
            // 🔑 allDay → solo data, niente end
            $start = Carbon::parse($validated['start'])
                ->setTimezone('Europe/Rome')
                ->startOfDay();

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $start->copy()->endOfDay(),
            ]);
        } else {
            // evento con orario
            $start = Carbon::parse($validated['start'])->setTimezone('Europe/Rome');
            $end   = Carbon::parse($request->end)->setTimezone('Europe/Rome');

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $end->toDateTimeString(),
            ]);
        }

        // return response()->json(['success' => true]);
    }
}

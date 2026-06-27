<?php

namespace App\Http\Controllers;

use App\Models\GridLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AnagraficaController extends Controller
{
    // ── Lista ──────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $tipoU   = $request->get('tipoU', 'U');
        $records = DB::table('anagrafica')
            ->where('B_TipoU', $tipoU)
            ->get()
            ->toArray();

        $gridKey     = "anagrafica_{$tipoU}";
        $savedLayout = GridLayout::forUser(auth()->id(), $gridKey);

        return Inertia::render('Anagrafica/Index', [
            'records'     => $records,
            'nomeTabella' => 'anagrafica',
            'tipoU'       => $tipoU,
            'savedLayout' => $savedLayout,
        ]);
    }

    // ── Nuovo form ─────────────────────────────────────────────
    public function create(): Response
    {
        return Inertia::render('Anagrafica/Form', [
            'anagrafica'  => null,
            'newCode'     => $this->generaNewCode(),
            'tipoU'       => 'U',
            ...$this->emptyTabProps(),
        ]);
    }

    // ── Salva nuovo ────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $this->validateAnagrafica($request);

        $id = DB::table('anagrafica')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('anagrafica.edit', $id)
            ->with('success', 'Record creato con successo.');
    }

    // ── Modifica form ──────────────────────────────────────────
    public function edit(int $id): Response
    {
        $record = DB::table('anagrafica')->where('IDAnagrafica', $id)->first();
        abort_if(!$record, 404);

        $cod = $record->CodCliente ?? '';

        return Inertia::render('Anagrafica/Form', [
            'anagrafica' => $record,
            'tipoU'      => $record->B_TipoU ?? 'U',
            ...$this->loadTabProps($cod),
        ]);
    }

    // ── Aggiorna ───────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        abort_if(
            !DB::table('anagrafica')->where('IDAnagrafica', $id)->exists(),
            404
        );

        $validated = $this->validateAnagrafica($request, $id);

        DB::table('anagrafica')
            ->where('IDAnagrafica', $id)
            ->update([...$validated, 'updated_at' => now()]);

        return back()->with('success', 'Record aggiornato con successo.');
    }

    // ── Elimina ────────────────────────────────────────────────
    public function destroy(int $id)
    {
        DB::table('anagrafica')->where('IDAnagrafica', $id)->delete();
        return redirect()->route('anagrafica.index')
            ->with('success', 'Record eliminato.');
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    /**
     * Carica i dati per i tab — se la tabella non esiste restituisce [].
     * ↓ ADATTA i nomi delle tabelle ai tuoi nomi reali ↓
     */
    private function loadTabProps(string $codCliente): array
    {
        return [
            'corsi'               => $this->queryCorsi($codCliente),
            'corsiDisponibili'    => $this->safeAll('TabCorsiFormazione'),
            'progetti'            => $this->safeQuery('progetti',       'CodCliente', $codCliente),
            'progettiDisponibili' => $this->safeAll('progetti_catalogo'),
            'NoteSpese'           => $this->safeQuery('note_spese',     'CodCliente', $codCliente),
            'Giornate'            => $this->safeQuery('giornate',       'CodCliente', $codCliente),
            'Contratti'           => $this->safeQuery('contratti',      'CodCliente', $codCliente),
            'ContrattiDisponibili'=> [],
            'Visite'              => $this->safeQuery('visite_mediche', 'CodCliente', $codCliente),
            'tipiContratto'       => $this->safeAll('tipi_contratto'),
            'Professione'         => $this->safeAll('professioni'),
            'attivita'            => [],
            'clienti'             => $this->safeAll('clienti'),
        ];
    }

    /**
     * Query reale corsi formazione utente.
     */
    private function queryCorsi(string $codCliente): array
    {
        try {
            return DB::select("
                SELECT
                    TabCorsiFormazioneUser.IdTabCorso,
                    TabCorsiFormazioneUser.CodCliente,
                    TabCorsiFormazioneUser.DataAttestato,
                    TabCorsiFormazione.TipoCorso,
                    TabCorsiFormazione.DurataAttestato,
                    TabCorsiFormazioneUser.Note,
                    TabCorsiFormazioneUser.Stato,
                    TabCorsiFormazioneUser.UtenteMod,
                    TabCorsiFormazioneUser.DataModifica
                FROM TabCorsiFormazioneUser
                INNER JOIN TabCorsiFormazione
                    ON TabCorsiFormazioneUser.IdCorso = TabCorsiFormazione.IdCorso
                WHERE TabCorsiFormazioneUser.CodCliente = ?
                ORDER BY TabCorsiFormazione.TipoCorso
            ", [$codCliente]);
        } catch (\Throwable) {
            return [];
        }
    }

    /** Props vuote per il form "crea nuovo" */
    private function emptyTabProps(): array
    {
        return [
            'corsi' => [], 'corsiDisponibili' => [],
            'progetti' => [], 'progettiDisponibili' => [],
            'NoteSpese' => [], 'Giornate' => [],
            'Contratti' => [], 'ContrattiDisponibili' => [],
            'Visite' => [], 'tipiContratto' => [],
            'Professione' => [], 'attivita' => [], 'clienti' => [],
        ];
    }

    /**
     * Query sicura: se la tabella non esiste ritorna [].
     * Sostituisci con le relazioni Eloquent quando hai i modelli.
     */
    private function safeQuery(string $table, string $column, string $value): array
    {
        try {
            if (!Schema::hasTable($table)) return [];
            return DB::table($table)->where($column, $value)->get()->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function safeAll(string $table): array
    {
        try {
            if (!Schema::hasTable($table)) return [];
            return DB::table($table)->get()->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Validazione ────────────────────────────────────────────
    private function validateAnagrafica(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'A_NomeVisualizzato'    => 'nullable|string|max:255',
            'B_TipoU'               => 'nullable|string|max:10',
            'C_Sede'                => 'nullable|string|max:100',
            'I_Cognome'             => 'required|string|max:100',
            'H_Nome'                => 'required|string|max:100',
            'J_Genere'              => 'nullable|string|max:1',
            'O_DataNascita'         => 'nullable|date',
            'CodCliente'            => 'nullable|string|max:50',
            'L_K_LuogoNascita'      => 'nullable|string|max:100',
            'M_SiglaNascita'        => 'nullable|string|max:5',
            'N_StatoNascita'        => 'nullable|string|max:100',
            'AL_Cittadinanza'       => 'nullable|string|max:100',
            'AE_IndirizzoEmail'     => 'nullable|email|max:255',
            'AD_Cellulare'          => 'nullable|string|max:30',
            'AF_Tel'                => 'nullable|string|max:30',
            'BA_CodiceUser'         => 'nullable|string|max:50',
            'P_IndirizzoResidenza'  => 'nullable|string|max:255',
            'R_ComuneResidenza'     => 'nullable|string|max:100',
            'Q_CAP_Residenza'       => 'nullable|string|max:10',
            'T_SiglaResidenza'      => 'nullable|string|max:5',
            'U_StatoResidenza'      => 'nullable|string|max:100',
            'V_IndirizzoDomicilio'  => 'nullable|string|max:255',
            'X_ComuneDomicilio'     => 'nullable|string|max:100',
            'W_CAP_Domicilio'       => 'nullable|string|max:10',
            'Z_SiglaDomicilio'      => 'nullable|string|max:5',
            'AA_StatoDomicilio'     => 'nullable|string|max:100',
            'TitSogg'               => 'nullable|string|max:100',
            'NTitSogg'              => 'nullable|string|max:50',
            'MotTitSogg'            => 'nullable|string|max:100',
            'ScaTitSogg'            => 'nullable|date',
            'QueTitSogg'            => 'nullable|string|max:100',
            'AG_CodiceFiscalePF'    => 'nullable|string|max:16',
            'AI_PartitaIVA'         => 'nullable|string|max:11',
            'AJ_RegimeAgevolato'    => 'nullable|string|max:50',
            'AssegniFam'            => 'nullable|string|max:10',
            'INPSridotta'           => 'nullable|string|max:10',
            'DetrLav'               => 'nullable|string|max:10',
            'BonusRenzi'            => 'nullable|string|max:10',
            'AW_IBAN'               => 'nullable|string|max:34',
            'AX_BIC_SWIFT'          => 'nullable|string|max:11',
            'Banca'                 => 'nullable|string|max:100',
            'Pensionato'            => 'nullable|string|max:10',
            'RapPublAmm'            => 'nullable|string|max:10',
            'AM_Professione1'       => 'nullable|string|max:100',
            'AO_UniLav'             => 'nullable|string|max:50',
            'LivelloCCNL'           => 'nullable|string|max:20',
            'AN_Professione2'       => 'nullable|string|max:100',
            'AN_Professione3'       => 'nullable|string|max:100',
            'AP_NumeroQuote'        => 'nullable|string|max:20',
            'AR_DataDomanda'        => 'nullable|date',
            'AS_DataApprovazioneCDA'=> 'nullable|date',
            'AT_DataVersamento'     => 'nullable|date',
            'NoQuotaSociale'        => 'nullable|string|max:10',
            'AU_DataRatifica'       => 'nullable|date',
            'BE_DataLettera'        => 'nullable|date',
            'BB_Note'               => 'nullable|string',
        ]);
    }

    private function generaNewCode(): string
    {
        $last = DB::table('anagrafica')->max('CodCliente');
        if (!$last) return 'U00001';
        $num = (int) preg_replace('/\D/', '', $last);
        return 'U' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\Anagrafica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnagraficaController extends Controller
{
    public function index(Request $request)
    {
        $tipoU = $request->query('tipoU', 'U');

        $records = Anagrafica::where('B_TipoU', $tipoU)
            ->select([
                'IDAnagrafica',
                'CodCliente',
                'B_TipoU',
                'A_NomeVisualizzato',
                'AI_PartitaIVA',
                'AH_CodiceFiscalePG',
                'AG_CodiceFiscalePF',
                'AE_IndirizzoEmail',
                'AD_Cellulare',
                'AM_Professione1',
                'AN_Professione2',
                'AN_Professione3',
                'R_ComuneResidenza',
                'AR_DataDomanda',
                'AS_DataApprovazioneCDA',
                'AT_DataVersamento',
                'AU_DataRatifica',
                'Stato',
                'VisitaMedica',
            ])
            ->orderBy('A_NomeVisualizzato')
            ->get();

        return Inertia::render('Anagrafica/Index', [
            'records' => $records,
            'columns' => [
                'IDAnagrafica', 'CodCliente', 'B_TipoU', 'A_NomeVisualizzato',
                'AI_PartitaIVA', 'AH_CodiceFiscalePG', 'AG_CodiceFiscalePF',
                'AE_IndirizzoEmail', 'AD_Cellulare', 'AM_Professione1',
                'AN_Professione2', 'AN_Professione3', 'R_ComuneResidenza',
                'AR_DataDomanda', 'AS_DataApprovazioneCDA', 'AT_DataVersamento',
                'AU_DataRatifica', 'Stato', 'VisitaMedica'
            ],
            'nomeTabella' => 'anagrafica',
            'tipoU' => $tipoU,
        ]);
    }

    public function list(Request $request)
    {
        $tipoU = $request->query('tipoU', 'U');

        return response()->json(
            Anagrafica::where('B_TipoU', $tipoU)
                ->select([
                    'IDAnagrafica', 'CodCliente', 'B_TipoU', 'A_NomeVisualizzato',
                    'AI_PartitaIVA', 'AH_CodiceFiscalePG', 'AG_CodiceFiscalePF',
                    'AE_IndirizzoEmail', 'AD_Cellulare', 'AM_Professione1',
                    'AN_Professione2', 'AN_Professione3', 'R_ComuneResidenza',
                    'AR_DataDomanda', 'AS_DataApprovazioneCDA', 'AT_DataVersamento',
                    'AU_DataRatifica', 'Stato', 'VisitaMedica'
                ])
                ->orderBy('A_NomeVisualizzato')
                ->limit(500)
                ->get()
        );
    }

    public function create()
{
    // Trova il massimo codice esistente che inizia per C
    $maxCode = DB::table('anagrafica') // metti il nome della tua tabella
        ->where('CodCliente', 'like', 'C%')
        ->max(DB::raw('CAST(SUBSTRING(CodCliente, 2) AS UNSIGNED)'));

    // Se non esiste ancora, parte da 1
    $nextNumber = ($maxCode ?? 0) + 1;

    // Formatta come C + numero a 6 cifre
    $newCode = 'C' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);



    $corsiDisponibili = DB::table('TabCorsiFormazione')
        ->select('IdCorso', 'TipoCorso')
        ->orderBy('TipoCorso')
        ->get();

    return Inertia::render('Anagrafica/Form', [
        'anagrafica' => null,
        'corsi' => [], // nessun corso utente da caricare in fase di creazione
        'corsiDisponibili' => $corsiDisponibili, // combo visibile
        'newCode' => $newCode,
    ]);
}


    public function store(Request $request)
{
    $data = $request->validate([
        // Generali
        'A_NomeVisualizzato'=> 'nullable|string|max:255',
        'B_TipoU' => 'required|string|max:1',
        'C_Sede' => 'nullable|string|max:255',
        'I_Cognome' => 'nullable|string|max:255',
        'H_Nome' => 'nullable|string|max:255',
        'J_Genere' => 'nullable|string|max:10',
        'O_DataNascita' => 'nullable|date',
        'L_K_LuogoNascita' => 'nullable|string|max:255',
        'M_SiglaNascita' => 'nullable|string|max:10',
        'N_StatoNascita' => 'nullable|string|max:255',
        'AL_Cittadinanza' => 'nullable|string|max:255',
        'AE_IndirizzoEmail' => 'nullable|email|max:255',
        'AD_Cellulare' => 'nullable|string|max:20',
        'AF_Tel' => 'nullable|string|max:20',


        // Residenza
        'P_IndirizzoResidenza' => 'nullable|string|max:255',
        'R_ComuneResidenza' => 'nullable|string|max:255',
        'Q_CAP_Residenza' => 'nullable|string|max:10',
        'T_SiglaResidenza' => 'nullable|string|max:10',
        'U_StatoResidenza' => 'nullable|string|max:255',

        // Domicilio
        'V_IndirizzoDomicilio' => 'nullable|string|max:255',
        'X_ComuneDomicilio' => 'nullable|string|max:255',
        'W_CAP_Domicilio' => 'nullable|string|max:10',
        'Z_SiglaDomicilio' => 'nullable|string|max:10',
        'AA_StatoDomicilio' => 'nullable|string|max:255',

        // Permesso di soggiorno
        'TitSogg' => 'nullable|string|max:255',
        'NTitSogg' => 'nullable|string|max:50',
        'MotTitSogg' => 'nullable|string|max:255',
        'ScaTitSogg' => 'nullable|date',
        'QueTitSogg' => 'nullable|string|max:255',

        // Dati fiscali
        'AG_CodiceFiscalePF' => 'nullable|string|max:20',
        'AI_PartitaIVA' => 'nullable|string|max:20',
        'AJ_RegimeAgevolato' => 'nullable|string|max:20',
        'AssegniFam' => 'nullable|string|max:10',
        'INPSridotta' => 'nullable|string|max:10',
        'DetrLav' => 'nullable|string|max:10',
        'BonusRenzi' => 'nullable|string|max:10',
        'AW_IBAN' => 'nullable|string|max:34',
        'AX_BIC_SWIFT' => 'nullable|string|max:11',
        'Banca' => 'nullable|string|max:255',
        'Pensionato' => 'nullable|string|max:10',
        'RapPublAmm' => 'nullable|string|max:10',

        // Professione
        'AM_Professione1' => 'nullable|string|max:255',
        'AO_UniLav' => 'nullable|string|max:255',
        'LivelloCCNL' => 'nullable|string|max:255',
        'AN_Professione2' => 'nullable|string|max:255',
        'AN_Professione3' => 'nullable|string|max:255',

        //libro Soci
        'AP_NumeroQuote' => 'nullable|integer',
        'AR_DataDomanda' => 'nullable|date',
        'AS_DataApprovazioneCDA' => 'nullable|date',
        'AT_DataVersamento' => 'nullable|date',
        'NoQuotaSociale' => 'nullable|string|max:255',
        'AU_DataRatifica' => 'nullable|date',
        'BE_DataLettera' => 'nullable|date',
        'BB_Note' => 'nullable|string',

    ]);

    Anagrafica::create($data);

    return redirect()->route('anagrafica.index')->with('success', 'Record creato con successo');
}

    public function edit($id)
    {
        // Elenco Contratti
        $tipiContratto = DB::table('elenchi_contratti')
        ->select('Tipo_di_contratto')
        ->orderBy('Tipo_di_contratto')
        ->pluck('Tipo_di_contratto')
        ->toArray();
        // Elenco Professioni
        $Professione = DB::table('tab_professioni')
        ->select('Professione')
        ->orderBy('Professione')
        ->pluck('Professione')
        ->toArray();


        $record = Anagrafica::findOrFail($id);

        // Formatta la data di nascita se presente
        if ($record->O_DataNascita) {
            $record->O_DataNascita = Carbon::parse($record->O_DataNascita)->format('Y-m-d');
        }
        if ($record->AR_DataDomanda) {
            $record->AR_DataDomanda = Carbon::parse($record->AR_DataDomanda)->format('Y-m-d');
        }
        if ($record->AS_DataApprovazioneCDA) {
            $record->AS_DataApprovazioneCDA = Carbon::parse($record->AS_DataApprovazioneCDA)->format('Y-m-d');
        }
        if ($record->AT_DataVersamento) {
            $record->AT_DataVersamento = Carbon::parse($record->AT_DataVersamento)->format('Y-m-d');
        }
        if ($record->AU_DataRatifica) {
            $record->AU_DataRatifica = Carbon::parse($record->AU_DataRatifica)->format('Y-m-d');
        }
        if ($record->BE_DataLettera) {
            $record->BE_DataLettera = Carbon::parse($record->BE_DataLettera)->format('Y-m-d');
        }


// 1. Query attività
$attivita = DB::table('attivita')
    ->where('CodCliente', $record->CodCliente)
    ->select('IDAttivita', 'CodCliente', 'NomeAttivita', 'DesProgetto')
    ->orderBy('IDAttivita')
    ->get();

// 2. Query progetti figli
$progettiFigli = DB::table('progetti')
    ->whereIn('IdAttivitA', $attivita->pluck('IDAttivita'))
    ->select(
        'IdProg',
        'IdAttivitA',
        'IdProgetto',
        DB::raw('`RagioneSocialeCommittenti` as RagioneSocialeCommittenti'),
        'TipologiaSimulatore',
        'DescrizioneProgetto',
        'DataInzProgetto',
        'DataFineProgetto',
        'DataPagamento',
        'Accordi',
        'Percentuale',
        'GGPag',
        'DataEmissionePrevFattura',
        'ImportoNettoConcordato',
        'AliquotaIVA',
        'EsenzioneIva',
        'CIG',
        'coproduzione',
        'DescrCausaleFattura',
        'IndirizzoEmailFattura',
        'IndirizzoEmailContatto',
        'StatoProgetto',
        'Consigliere',
        'ImportGiornate',
        'Pranzo',
        'Cena',
        'Alloggio',
        'NNotti',
        'Viaggio',
        'CUP',
        'NOrdineCup',
        'DtOrdineCup',
        'Titolo',
        'Autore',
        'RegiaCoreografia',
        'NumRepliche',
        'CodCliente'


    )
    ->get()
    ->groupBy('IdAttivitA');

// 3. Assegna i figli
$attivita = $attivita->map(function ($a) use ($progettiFigli) {
    $a->progetti = $progettiFigli[$a->IDAttivita] ?? [];
    return $a;
});



        // Progetti associati al cliente
        $query = DB::table('progetti')
            ->join('attivita', 'attivita.IDAttivita', '=', 'progetti.IdAttivita')
            ->select([
                'progetti.IdProgetto as IdProgetto',
        'progetti.IdAttivita as IdAttivita',
        'progetti.IdProg as IdProg',
        'progetti.CodCliente as CodCliente',               // 👈 prefissato
        'progetti.RagioneSocialeCommittenti as RagioneSocialeCommittenti',
        'progetti.TipologiaSimulatore as TipologiaSimulatore',
        'progetti.DataInzProgetto as DataInzProgetto',
        'progetti.DataFineProgetto as DataFineProgetto',
        'progetti.DataPagamento as DataPagamento',
        'progetti.Accordi as Accordi',
        'progetti.Percentuale as Percentuale',
        'progetti.GGPag as GGPag',
        'progetti.DataEmissionePrevFattura as DataEmissionePrevFattura',
        'progetti.ImportoNettoConcordato as ImportoNettoConcordato',
        'progetti.AliquotaIVA as AliquotaIVA',
        'progetti.EsenzioneIva as EsenzioneIva',
        'progetti.CIG as CIG',
        'progetti.coproduzione as coproduzione',
        'progetti.DescrCausaleFattura as DescrCausaleFattura',
        'progetti.IndirizzoEmailFattura as IndirizzoEmailFattura',
        'progetti.IndirizzoEmailContatto as IndirizzoEmailContatto',
        'progetti.StatoProgetto as StatoProgetto',
        'progetti.Consigliere as Consigliere',
        'progetti.ImportGiornate as ImportGiornate',
        'progetti.Pranzo as Pranzo',
        'progetti.Cena as Cena',
        'progetti.Alloggio as Alloggio',
        'progetti.NNotti as NNotti',
        'progetti.Viaggio as Viaggio',
        'progetti.CUP as CUP',
        'progetti.NOrdineCup as NOrdineCup',
        'progetti.DtOrdineCup as DtOrdineCup',
        'progetti.Note as Note',
        // se ti serve anche il CodCliente di attivita, alias separato:
        // 'attivita.CodCliente as AttivitaCodCliente',

            ])
            ->orderBy('progetti.IdAttivita');

            if (!empty($record->CodCliente)) {
                $query->where('progetti.CodCapoProgetto', $record->CodCliente);
            }

        $progetti = $query->get();

        // Progetti non ancora assegnati (disponibili)
        $progettiDisponibili = DB::table('progetti')
        ->whereNull('CodCliente')
        ->select('IdProg', 'DescrizioneProgetto')
        ->orderBy('IdProg')
        ->get();


        // NoteSpese associati al cliente
        $query = DB::table('vistanotaspesa')
        ->select([
            'vistanotaspesa.CodCliente as CodCliente',
            'vistanotaspesa.DataDoc as DataDoc',
            'vistanotaspesa.DataPag as DataPag',
            'vistanotaspesa.Coddoc as Coddoc',
            'vistanotaspesa.CodAtt as CodAtt',
            'vistanotaspesa.Cliente_Fornitore as Cliente_Fornitore',
            'vistanotaspesa.Causale_Banca_Note_Spese as Causale_Banca_Note_Spese',
            'vistanotaspesa.note as note',
            'vistanotaspesa.Neg as Neg',

            ])
        ->orderBy('vistanotaspesa.DataDoc', 'desc');

        if (!empty($record->CodCliente)) {
            $query->where('vistanotaspesa.CodCliente', $record->CodCliente);
        }

        $NoteSpese = $query->get();



        // NoteSpese non ancora assegnati (disponibili)
        $NoteSpeseDisponibili = DB::table('vistanotaspesa')
        ->whereNull('CodCliente')
        ->select('DataDoc', 'DataPag','Coddoc','CodAtt','Cliente_Fornitore','Causale_Banca_Note_Spese','note','Neg')
        ->orderBy('DataDoc')
        ->get();


            // Giornate associati al cliente
            $query = DB::table('vistagiornate')
            ->select([
            'vistagiornate.CodCliente',
            'vistagiornate.IDContratto',
            'vistagiornate.CodiceAttivita',
            'vistagiornate.IdGiornate',
            'vistagiornate.Data',
            'vistagiornate.Diaria',
            'vistagiornate.Descrizioneprogetto',
            'vistagiornate.Retribuzione',
            'vistagiornate.IdProg',
            'vistagiornate.IdProgetto',
            'vistagiornate.TipoContrSimulatore',


                ])
            ->orderBy('vistagiornate.Data', 'desc');

                if (!empty($record->CodCliente)) {
                $query->where('vistagiornate.CodCliente', $record->CodCliente);
            }

            $Giornate = $query->get();



            // Giornate non ancora assegnati (disponibili)
            $GiornateDisponibili = DB::table('vistagiornate')
            ->whereNull('CodCliente')
            ->select('CodCliente','Diaria', 'IDContratto','CodiceAttivita','IdGiornate','Data','Descrizioneprogetto','Retribuzione','IdProg','IdProgetto')
            ->orderBy('Data')
            ->get();

            // Contratti associati al cliente
            $query = DB::table('contratti')
                ->select([
                    'contratti.IdContratti',
                    'contratti.IdContratto',
                    'contratti.NomeCognUser',
                    'contratti.TipoContr',
                    'contratti.DataContratto',
                    'contratti.DataInizio',
                    'contratti.DataFineContratto',
                    'contratti.CodCliente',
                    DB::raw("IF(contratti.DataFineContratto < CURDATE(), 'Scaduto', 'in Vigore') AS StatoContratto"),
                    'contratti.Stato',
                    'contratti.CodFiscale',
                ])
                ->orderBy('contratti.DataInizio', 'desc');

                if (!empty($record->CodCliente)) {
                $query->where('contratti.CodCliente', $record->CodCliente);
            }

            $Contratti = $query->get();

            // Contratti non ancora assegnati (disponibili)
            $ContrattiDisponibili = DB::table('contratti')
            ->whereNull('CodCliente')
            ->select('CodCliente','IdContratti', 'IdContratto','NomeCognUser','TipoContr','DataContratto','DataInizio','DataFineContratto','StatoContratto','Stato','CodFiscale')
            ->orderBy('DataInizio','desc')
            ->get();


            $corsiDisponibili = DB::table('TabCorsiFormazione')
            ->select('IdCorso', 'TipoCorso')
            ->orderBy('TipoCorso')
            ->get();

            $corsi = DB::table('TabCorsiFormazioneUser')
            ->join('TabCorsiFormazione', 'TabCorsiFormazioneUser.IdCorso', '=', 'TabCorsiFormazione.IdCorso')
            ->where('TabCorsiFormazioneUser.CodCliente', $record->CodCliente)
            ->select(
                'TabCorsiFormazioneUser.IdTabCorso',
                'TabCorsiFormazioneUser.CodCliente',
                'TabCorsiFormazioneUser.DataAttestato',
                'TabCorsiFormazione.TipoCorso',
                'TabCorsiFormazione.DurataAttestato',
                'TabCorsiFormazioneUser.Note',
                'TabCorsiFormazioneUser.Stato',
                'TabCorsiFormazioneUser.UtenteMod',
                'TabCorsiFormazioneUser.DataModifica',
            )
            ->orderBy('TabCorsiFormazione.TipoCorso')
            ->get();



            $clienti = DB::table('anagrafica')
            ->where('B_TipoU', 'C')
            ->orderBy('A_NomeVisualizzato')
            ->pluck('A_NomeVisualizzato', 'CodCliente');



            return Inertia::render('Anagrafica/Form', [
                'anagrafica' => $record,
                'corsi' => $corsi,
                'corsiDisponibili' => $corsiDisponibili,
                'progetti' => $progetti,
                'progettiDisponibili' => $progettiDisponibili,
                'NoteSpese' => $NoteSpese,
                'NoteSpeseDisponibili' => $NoteSpeseDisponibili,
                'Giornate' => $Giornate,
                'GiornateDisponibili' => $GiornateDisponibili,
                'Contratti' => $Contratti,
                'ContrattiDisponibili' => $ContrattiDisponibili,
                'attivita' => $attivita,
                'tipiContratto' => $tipiContratto,
                'Professione' => $Professione,
                'clienti' => $clienti

            ]);
    }

    public function update(Request $request, Anagrafica $anagrafica)
{
    $data = $request->validate([
        // stessi campi del metodo store()
        'CodCliente' => 'required|string|max:7',
        'A_NomeVisualizzato' => 'nullable|string|max:255',
        'B_TipoU' => 'required|string|max:1',
        'C_Sede' => 'nullable|string|max:255',
        'I_Cognome' => 'nullable|string|max:255',
        'H_Nome' => 'nullable|string|max:255',
        'J_Genere' => 'nullable|string|max:10',
        'O_DataNascita' => 'nullable|date',
        'L_K_LuogoNascita' => 'nullable|string|max:255',
        'M_SiglaNascita' => 'nullable|string|max:10',
        'N_StatoNascita' => 'nullable|string|max:255',
        'AL_Cittadinanza' => 'nullable|string|max:255',
        'AE_IndirizzoEmail' => 'nullable|email|max:255',
        'AD_Cellulare' => 'nullable|string|max:20',
        'AF_Tel' => 'nullable|string|max:20',
        'P_IndirizzoResidenza' => 'nullable|string|max:255',
        'R_ComuneResidenza' => 'nullable|string|max:255',
        'Q_CAP_Residenza' => 'nullable|string|max:10',
        'T_SiglaResidenza' => 'nullable|string|max:10',
        'U_StatoResidenza' => 'nullable|string|max:255',
        'V_IndirizzoDomicilio' => 'nullable|string|max:255',
        'X_ComuneDomicilio' => 'nullable|string|max:255',
        'W_CAP_Domicilio' => 'nullable|string|max:10',
        'Z_SiglaDomicilio' => 'nullable|string|max:10',
        'AA_StatoDomicilio' => 'nullable|string|max:255',
        'TitSogg' => 'nullable|string|max:255',
        'NTitSogg' => 'nullable|string|max:50',
        'MotTitSogg' => 'nullable|string|max:255',
        'ScaTitSogg' => 'nullable|date',
        'QueTitSogg' => 'nullable|string|max:255',
        'AG_CodiceFiscalePF' => 'nullable|string|max:20',
        'AI_PartitaIVA' => 'nullable|string|max:20',
        'AJ_RegimeAgevolato' => 'nullable|string|max:20',
        'AssegniFam' => 'nullable|string|max:10',
        'INPSridotta' => 'nullable|string|max:10',
        'DetrLav' => 'nullable|string|max:10',
        'BonusRenzi' => 'nullable|string|max:10',
        'AW_IBAN' => 'nullable|string|max:34',
        'AX_BIC_SWIFT' => 'nullable|string|max:11',
        'Banca' => 'nullable|string|max:255',
        'Pensionato' => 'nullable|string|max:10',
        'RapPublAmm' => 'nullable|string|max:10',
        'AM_Professione1' => 'nullable|string|max:255',
        'AO_UniLav' => 'nullable|string|max:255',
        'LivelloCCNL' => 'nullable|string|max:255',
        'AN_Professione2' => 'nullable|string|max:255',
        'AN_Professione3' => 'nullable|string|max:255',
        'AP_NumeroQuote' => 'nullable|integer',
        'AR_DataDomanda' => 'nullable|date',
        'AS_DataApprovazioneCDA' => 'nullable|date',
        'AT_DataVersamento' => 'nullable|date',
        'NoQuotaSociale' => 'nullable|string|max:255',
        'AU_DataRatifica' => 'nullable|date',
        'BE_DataLettera' => 'nullable|date',
        'BB_Note' => 'nullable|string',

    ]);

    $anagrafica->update($data);

    return redirect()->route('anagrafica.index')->with('success', 'Record aggiornato con successo.');
}


    public function destroy(Anagrafica $anagrafica)
    {
        $anagrafica->delete();

        return redirect()->back()->with('success', 'Record eliminato con successo');
    }
}

<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Anagrafica extends Model
    {
        use HasFactory;

        protected $table = 'anagrafica';
        protected $primaryKey = 'IDAnagrafica';

        public $timestamps = false; // ✅ Disattiva updated_at / created_at

        protected $fillable = [
            // Tipo Utente
            'B_TipoU',
            'C_Sede',

            // Dati anagrafici
            'A_NomeVisualizzato',
            'I_Cognome',
            'H_Nome',
            'J_Genere',
            'O_DataNascita',
            'L_K_LuogoNascita',
            'M_SiglaNascita',
            'N_StatoNascita',
            'AL_Cittadinanza',

            // Contatti
            'AE_IndirizzoEmail',
            'AD_Cellulare',
            'AF_Tel',
            'CodCliente',

            // Residenza
            'P_IndirizzoResidenza',
            'R_ComuneResidenza',
            'Q_CAP_Residenza',
            'T_SiglaResidenza',
            'U_StatoResidenza',

            // Domicilio
            'V_IndirizzoDomicilio',
            'X_ComuneDomicilio',
            'W_CAP_Domicilio',
            'Z_SiglaDomicilio',
            'AA_StatoDomicilio',

            // Permesso di soggiorno
            'TitSogg',
            'NTitSogg',
            'MotTitSogg',
            'ScaTitSogg',
            'QueTitSogg',

            // Dati fiscali
            'AG_CodiceFiscalePF',
            'AI_PartitaIVA',
            'AJ_RegimeAgevolato',
            'AssegniFam',
            'INPSridotta',
            'DetrLav',
            'BonusRenzi',
            'AW_IBAN',
            'AX_BIC_SWIFT',
            'Banca',
            'Pensionato',
            'RapPublAmm',

            //Libro Soci
            'AP_NumeroQuote',
            'AR_DataDomanda',
            'AS_DataApprovazioneCDA',
            'AT_DataVersamento',
            'NoQuotaSociale',
            'AU_DataRatifica',
            'BE_DataLettera',
            'BB_Note',

            // Altri campi vari
            'AO_UniLav',
            'AH_CodiceFiscalePG',
            'AM_Professione1',
            'AN_Professione2',
            'AN_Professione3',
            'Stato',
            'VisitaMedica',
            'Fido',
            'LivelloCCNL',


        ];
    }

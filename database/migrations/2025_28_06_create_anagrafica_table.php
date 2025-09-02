<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anagrafica', function (Blueprint $table) {
            $table->id('IDAnagrafica');
            $table->string('CodCliente', 7)->nullable();
            $table->string('A_NomeVisualizzato')->nullable();
            $table->string('B_TipoU')->nullable();
            $table->string('C_Sede')->nullable();
            $table->string('D_IdSocio')->nullable();
            $table->string('E_RagSociale')->nullable();
            $table->string('F_CodiceUnivocoUfficio')->nullable();
            $table->string('G_AcronimoCommittente')->nullable();
            $table->string('H_Nome')->nullable();
            $table->string('I_Cognome')->nullable();
            $table->string('J_Genere')->nullable();
            $table->string('L_K_LuogoNascita')->nullable();
            $table->string('M_SiglaNascita')->nullable();
            $table->string('N_StatoNascita')->nullable();
            $table->dateTime('O_DataNascita')->nullable();
            $table->string('P_IndirizzoResidenza')->nullable();
            $table->string('Q_CAP_Residenza')->nullable();
            $table->string('R_ComuneResidenza')->nullable();
            $table->string('S_ComuneResidenzaStraniero')->nullable();
            $table->string('T_SiglaResidenza')->nullable();
            $table->string('U_StatoResidenza')->nullable();
            $table->string('V_IndirizzoDomicilio')->nullable();
            $table->string('W_CAP_Domicilio')->nullable();
            $table->string('X_ComuneDomicilio')->nullable();
            $table->string('Y_ComuneDomicilioStraniero')->nullable();
            $table->string('Z_SiglaDomicilio')->nullable();
            $table->string('AA_StatoDomicilio')->nullable();
            $table->string('AB_NomeContattoCliente')->nullable();
            $table->string('AC_CognomeContattoCliente')->nullable();
            $table->string('AD_Cellulare')->nullable();
            $table->string('AE_IndirizzoEmail')->nullable();
            $table->string('IndirizzoEmailPEC')->nullable();
            $table->string('AF_Tel')->nullable();
            $table->string('AG_CodiceFiscalePF')->nullable();
            $table->string('AH_CodiceFiscalePG')->nullable();
            $table->string('AI_PartitaIVA')->nullable();
            $table->string('AJ_RegimeAgevolato')->nullable();
            $table->string('AK_MatricolaENPALS')->nullable();
            $table->string('AL_Cittadinanza')->nullable();
            $table->string('AM_Professione1')->nullable();
            $table->string('AN_Professione2')->nullable();
            $table->string('AN_Professione3')->nullable();
            $table->string('AO_UniLav')->nullable();
            $table->string('AP_NumeroQuote')->nullable();
            $table->string('AQ_IscrizioneNewsletter')->nullable();
            $table->dateTime('AR_DataDomanda')->nullable();
            $table->dateTime('AS_DataApprovazioneCDA')->nullable();
            $table->dateTime('AT_DataVersamento')->nullable();
            $table->dateTime('AU_DataRatifica')->nullable();
            $table->string('AV_ModalitaPagamento')->nullable();
            $table->string('AW_IBAN')->nullable();
            $table->string('AX_BIC-SWIFT')->nullable();
            $table->dateTime('AY_DataScheda')->nullable();
            $table->dateTime('AZ_DataInfosession')->nullable();
            $table->string('BA_CodiceUser')->nullable();
            $table->string('BB_Note')->nullable();
            $table->string('BC_Eta')->nullable();
            $table->dateTime('BD_DataIscrizioneLibroSoci')->nullable();
            $table->dateTime('BE_DataLettera')->nullable();
            $table->string('BF_Ritardo')->nullable();
            $table->string('BG_GiorniMediRitardo')->nullable();
            $table->string('BH_AttivitaRealizzate')->nullable();
            $table->dateTime('BI_DataUltimoContrattoAttivo')->nullable();
            $table->string('Annotazioni')->nullable();
            $table->string('CodUser', 8)->nullable();
            $table->string('IndirizzoEmailFattura')->nullable();
            $table->decimal('Fido', 15, 2)->nullable();
            $table->string('Stato')->nullable();
            $table->float('IdRigaExcel')->nullable();
            $table->string('SplitPay', 2)->nullable();
            $table->float('IdCreazione')->nullable();
            $table->string('LivelloCCNL', 5)->nullable();
            $table->string('Banca')->nullable();
            $table->string('AssegniFam')->nullable();
            $table->string('INPSridotta')->nullable();
            $table->string('DetrLav')->nullable();
            $table->string('BonusRenzi')->nullable();
            $table->string('Pensionato', 2)->nullable();
            $table->string('RapPublAmm', 2)->nullable();
            $table->char('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->string('NoQuotaSociale', 2)->nullable();
            $table->string('VisitaMedica', 2)->nullable();
            $table->string('LavQuota', 2)->nullable();
            $table->char('NotaMed', 50)->nullable();
            $table->char('Modifiche', 255)->nullable();
            $table->string('TitSogg', 55)->nullable();
            $table->string('NTitSogg', 55)->nullable();
            $table->string('MotTitSogg', 55)->nullable();
            $table->string('ScaTitSogg', 55)->nullable();
            $table->string('QueTitSogg', 55)->nullable();

            $table->primary('IDAnagrafica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anagrafica');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prima_nota', function (Blueprint $table) {
            $table->increments('IDPrimaNota');

            $table->dateTime('DataPagPrev')->nullable();
            $table->dateTime('DataPag')->nullable();
            $table->dateTime('DataDoc')->nullable();
            $table->float('Anno')->nullable();
            $table->string('RifForNoteSpese')->nullable();
            $table->string('Cliente_Fornitore')->nullable();
            $table->string('Causale_Banca_Note_Spese')->nullable();
            $table->string('note')->nullable();
            $table->string('Descrizione')->nullable();
            $table->string('TipDocF')->nullable();
            $table->string('TipDoc')->nullable();
            $table->string('ModPagamento')->nullable();
            $table->float('Plus')->nullable();           // [+]
            $table->float('Minus')->nullable();          // [-]
            $table->string('CostoLavoro')->nullable();
            $table->string('Errore')->nullable();
            $table->float('Budget')->nullable();
            $table->float('Importo_totale')->nullable();
            $table->float('Entrate_Contanti')->nullable();
            $table->float('Uscite_Contanti')->nullable();
            $table->float('Saldo_Contanti')->nullable();
            $table->float('Entrate_Banca')->nullable();
            $table->float('Uscite_Banca')->nullable();
            $table->float('Saldo_Banca')->nullable();
            $table->float('Entrate_CC')->nullable();
            $table->float('Uscite_CC')->nullable();
            $table->float('Saldo_Cc')->nullable();
            $table->string('Statoattivita')->nullable();
            $table->string('Codice_SMartIt')->nullable();
            $table->string('Terzo')->nullable();         // [3°]
            $table->string('Secondo')->nullable();       // [2°]
            $table->string('Primo')->nullable();         // [1°]
            $table->string('Responsabile')->nullable();
            $table->float('Imponibile')->nullable();
            $table->float('INPS')->nullable();
            $table->float('INAIL')->nullable();
            $table->float('Oneri_sociali')->nullable();
            $table->float('Oneri_carico_lavoratore')->nullable();
            $table->float('Contributi_Carico_Azienda')->nullable();
            $table->float('IRPEF')->nullable();
            $table->float('Saldo_Pagare')->nullable();
            $table->float('IVA')->nullable();
            $table->float('IRAP')->nullable();
            $table->float('Costo_Ricavo_Totale')->nullable();
            $table->float('Differenza')->nullable();
            $table->string('Nome_attivita')->nullable();
            $table->string('Nome_progetto')->nullable();
            $table->string('Codice_User')->nullable();
            $table->float('Calcolo_budget')->nullable();
            $table->string('Socio')->nullable();
            $table->string('FatturaEmessaRiportaCodProgetto')->nullable();
            $table->dateTime('Data_ricavo_effettiva')->nullable();
            $table->dateTime('Data_ricavo_prevista')->nullable();
            $table->dateTime('Data_prevista')->nullable();
            $table->float('Ritardo_da_previsione')->nullable();
            $table->float('Ritardo_da_fattura')->nullable();
            $table->float('Ritardo_medio')->nullable();
            $table->dateTime('DataScadenzaFatt')->nullable();
            $table->string('RifFattOrigine')->nullable();
            $table->string('CodCliente', 7)->nullable();
            $table->string('CodClienteUser', 7)->nullable();
            $table->char('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->string('Coddoc', 20)->nullable();
            $table->string('CodAtt', 20)->nullable();
            $table->string('NFattura', 40)->nullable();
            $table->string('NotaPrimaNota')->nullable();
            $table->string('InvioComm', 2)->default('No');
            $table->string('StatoFattura', 50)->nullable();
            $table->integer('IdTabnotaspesa')->nullable();
            $table->float('AbbuoniPos')->default(0);
            $table->float('AbbuoniNeg')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prima_nota');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_ordine', function (Blueprint $table) {
            $table->increments('ID'); // int identity PK

            $table->integer('Nordine')->nullable();

            // Anagrafica
            $table->string('CognomeNome', 50)->nullable();
            $table->string('Telefono', 25)->nullable();
            $table->string('Cellulare', 25)->nullable();
            $table->string('Indirizzo', 50)->nullable();
            $table->string('IdCitta', 255)->nullable();          // (ex IdCittà)
            $table->string('Provincia', 50)->nullable();
            $table->string('CAP', 15)->nullable();
            $table->longText('Annotazioni')->nullable();
            $table->string('CodFiscale', 30)->nullable();
            $table->string('PIva', 30)->nullable();

            // Fatturazione
            $table->string('CognomeNomeFatt', 50)->nullable();
            $table->string('TelefonoFatt', 25)->nullable();
            $table->string('CellulareFatt', 25)->nullable();
            $table->string('IndirizzoFatt', 50)->nullable();
            $table->string('IdCittaFatt', 50)->nullable();       // (ex IdCittàFatt)
            $table->string('ProvinciaFatt', 50)->nullable();
            $table->string('CAPFatt', 15)->nullable();
            $table->longText('AnnotazioniFatt')->nullable();
            $table->string('CodFiscaleFatt', 30)->nullable();
            $table->string('PIvaFatt', 30)->nullable();

            // Sconti / listino / flag
            $table->double('Sconto1')->nullable();
            $table->double('Sconto2')->nullable();
            $table->double('Listino')->nullable();

            $table->boolean('CkRilievo')->nullable();
            $table->boolean('CkMontaggio')->nullable();
            $table->string('SelTrasporto', 255)->nullable();
            $table->string('IdZonaClimatica', 255)->nullable();
            $table->string('Fascia', 30)->nullable();
            $table->string('UgFascia', 30)->nullable();
            $table->string('IdAgente', 255)->nullable();
            $table->dateTime('DataOrdine')->nullable();
            $table->string('LivMare', 30)->nullable();
            $table->double('ImportoMan')->nullable();
            $table->string('Progettista', 50)->nullable();
            $table->dateTime('DataCons')->nullable();
            $table->string('TipoDoc', 255)->nullable();
            $table->boolean('CkImballo')->nullable();

            $table->integer('IdIva')->nullable();
            $table->integer('KmMontaggio')->nullable();
            $table->double('CstMontaggio')->nullable();
            $table->double('CstTrasporto')->nullable();

            $table->string('TxtConsegna', 255)->nullable();
            $table->string('TxtModPagamento', 255)->nullable();

            $table->string('CodCliFor', 7)->nullable();
            $table->string('DesCliFor', 255)->nullable();
            $table->string('Agente', 255)->nullable();
            $table->string('Email', 255)->nullable();

            $table->string('OrdVetro', 255)->nullable();
            $table->string('OrdBugna', 255)->nullable();
            $table->string('RifOrdVetro', 255)->nullable();
            $table->string('RifOrdBugna', 255)->nullable();
            $table->string('Provv', 255)->nullable();

            $table->integer('IdTipoDoc')->nullable();
            $table->dateTime('DataFattura')->nullable();
            $table->boolean('CkRagFatt')->nullable();
            $table->string('Variabili',2000)->nullable();
            $table->boolean('OrdineSel')->nullable();
            $table->boolean('Ultimo')->nullable();
            $table->boolean('Produci')->nullable();
            $table->boolean('Prodotto')->nullable();

            $table->char('Utente', 50)->nullable(); // nchar(50)
            $table->dateTime('DataProduzione')->nullable();
            $table->dateTime('DataProdotto')->nullable();
            $table->dateTime('DataConferma')->nullable();

            // opzionale: se cerchi spesso per Nordine
            $table->index('Nordine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_ordine');
    }
};

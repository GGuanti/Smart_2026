<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contratti', function (Blueprint $table) {
            $table->increments('IdContratti'); // chiave primaria INT IDENTITY

            $table->string('IdContratto')->nullable();
            $table->float('NProg')->nullable();
            $table->float('Minima_giornata')->nullable();
            $table->string('Tabella_Paga')->nullable();
            $table->string('NomeCognUser')->nullable();
            $table->string('IdProgetto')->nullable();
            $table->string('TipoContr')->nullable();
            $table->string('DesPrest')->nullable();
            $table->float('Retribuzione')->nullable();
            $table->float('DurataGG')->nullable();
            $table->dateTime('DataInizio')->nullable();
            $table->dateTime('DataContratto')->nullable();
            $table->dateTime('DataFineContratto')->nullable();
            $table->string('GenereUser')->nullable();
            $table->string('LuogoNascitaUser')->nullable();
            $table->dateTime('DataNascitaUser')->nullable();
            $table->string('IndirizzoUser')->nullable();
            $table->string('CellUser')->nullable();
            $table->string('EmailUser')->nullable();
            $table->string('CodFiscale')->nullable();
            $table->float('PIvaUser')->nullable();
            $table->string('Professione')->nullable();
            $table->string('ModPag')->nullable();
            $table->string('IBanUser')->nullable();
            $table->string('RagSoCComm')->nullable();
            $table->string('NomeAttivita')->nullable();
            $table->string('DesAttivita')->nullable();
            $table->string('DesProgetto')->nullable();
            $table->string('Autore')->nullable();
            $table->string('RegiaCoreagrafia')->nullable();
            $table->string('RespProg')->nullable();
            $table->string('ObbProgFormativo')->nullable();
            $table->string('ModSvolgimento')->nullable();
            $table->string('Firma_Carica')->nullable();
            $table->string('FirmaNomeCognome')->nullable();
            $table->string('FirmaCodFiscale')->nullable();
            $table->string('CodUser', 8)->nullable();
            $table->string('LuogoData')->nullable();
            $table->float('Eta')->nullable();
            $table->string('StatoContratto')->nullable();
            $table->string('File')->nullable();
            $table->string('CodCliente', 7)->nullable();
            $table->string('Stato')->nullable();
            $table->string('CCNL')->nullable();
            $table->char('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->char('NotaContratto', 70)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratti');
    }
};

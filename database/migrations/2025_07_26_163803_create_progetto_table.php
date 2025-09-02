<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('progetti', function (Blueprint $table) {
            $table->id('IdProg');
            $table->string('IdProgetto')->nullable();
            $table->float('IdAttivita')->nullable();
            $table->string('RagioneSocialeCommittenti')->nullable();
            $table->string('SettoreDiAttivita')->nullable();
            $table->string('DescrizioneProgettoCanc')->nullable();
            $table->string('VittoAlloggioETrasporto')->nullable();
            $table->string('Accordi')->nullable();
            $table->integer('Percentuale')->nullable();
            $table->float('ImportoNettoConcordato')->nullable();
            $table->float('AliquotaIVA')->nullable();
            $table->dateTime('DataContratto')->nullable();
            $table->dateTime('DataInzProgetto')->nullable();
            $table->dateTime('DataFineProgetto')->nullable();
            $table->dateTime('DataPagamento')->nullable();
            $table->float('GGPag')->nullable();
            $table->dateTime('DataEmissionePrevFattura')->nullable();
            $table->string('DescrCausaleFattura')->nullable();
            $table->float('NumRepliche')->nullable();
            $table->string('NoteCanc')->nullable();
            $table->string('FirmaCarica')->nullable();
            $table->string('Animazione')->nullable();
            $table->string('Audiovisivo')->nullable();
            $table->string('Comunicazione')->nullable();
            $table->string('Consulenza')->nullable();
            $table->string('Coreografia')->nullable();
            $table->string('Danza')->nullable();
            $table->string('Editoria')->nullable();
            $table->string('Formazione')->nullable();
            $table->string('Generale')->nullable();
            $table->string('Interdisciplinare')->nullable();
            $table->string('Musica')->nullable();
            $table->string('Regia')->nullable();
            $table->string('Ricerca')->nullable();
            $table->string('Teatro')->nullable();
            $table->string('Altro')->nullable();
            $table->string('File')->nullable();
            $table->string('Contratto')->nullable();
            $table->string('CodCliente', 7)->nullable();
            $table->string('CodCapoProgetto', 7)->nullable();
            $table->string('CodUser', 8)->nullable();
            $table->string('FileSimulatore')->nullable();
            $table->string('FileContratto')->nullable();
            $table->string('IndirizzoEmailFattura')->nullable();
            $table->string('StatoProgetto')->nullable();
            $table->string('MacrocategoriaAttivita', 50)->nullable();
            $table->string('CIG', 10)->nullable();
            $table->string('RegiaCoreografia')->nullable();
            $table->string('Autore')->nullable();
            $table->string('Titolo')->nullable();
            $table->string('TipologiaSimulatore')->nullable();
            $table->dateTime('DataEmissFattura')->nullable();
            $table->string('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->float('DispResidua')->nullable();
            $table->float('DispResiduaPrec')->nullable();
            $table->string('EsenzioneIva', 10)->nullable();
            $table->string('ImportGiornate', 2)->nullable();
            $table->string('Consigliere', 50)->nullable();
            $table->string('IndirizzoEmailContatto')->nullable();
            $table->string('Pranzo', 50)->nullable();
            $table->string('Cena', 50)->nullable();
            $table->string('Alloggio', 50)->nullable();
            $table->string('NNotti', 50)->nullable();
            $table->string('Viaggio', 50)->nullable();
            $table->string('CUP', 50)->nullable();
            $table->dateTime('DtOrdineCup')->nullable();
            $table->string('NOrdineCup', 50)->nullable();
            $table->string('RendCariplo', 50)->nullable();
            $table->text('DescrizioneProgetto')->nullable();
            $table->text('Note')->nullable();
            $table->string('IdAccordoQuadro', 50)->nullable();
            $table->string('coproduzione', 1)->nullable();

            $table->primary('IdProg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progetto');
    }
};

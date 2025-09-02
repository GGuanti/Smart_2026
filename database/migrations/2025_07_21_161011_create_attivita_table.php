<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attivita', function (Blueprint $table) {
            $table->id('IDAttivita'); // Autoincrement e chiave primaria
            $table->string('TipologiaAttivita')->nullable();
            $table->string('CodCliente')->nullable();
            $table->string('NomeAttivita')->nullable();
            $table->string('NomeProgetto')->nullable();
            $table->string('Note')->nullable();
            $table->string('DesProgetto')->nullable();
            $table->string('Autore')->nullable();
            $table->string('RegiaCoreografia')->nullable();
            $table->string('RespProgetto')->nullable();
            $table->string('ObProgFormativo')->nullable();
            $table->string('ModSvolgProgFor')->nullable();
            $table->string('CodRespProgetto')->nullable();
            $table->string('UtenteMod')->nullable();
            $table->timestamp('DataModifica')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attivita');
    }
};

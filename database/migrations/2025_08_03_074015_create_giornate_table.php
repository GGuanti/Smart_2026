<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('giornate', function (Blueprint $table) {
            $table->id('IdGiornate'); // Numerazione automatica (chiave primaria)
            $table->string('IDContratto')->nullable();
            $table->string('Denominazione_luogo')->nullable();
            $table->dateTime('Data')->nullable();
            $table->string('Indirizzo')->nullable();
            $table->string('CAP')->nullable();
            $table->string('Comune')->nullable();
            $table->string('Comune_straniero')->nullable();
            $table->string('Provincia')->nullable();
            $table->string('Sigla')->nullable();
            $table->string('Stato')->nullable();
            $table->decimal('Retribuzione', 10, 2)->nullable();
            $table->boolean('DIARIA')->nullable();
            $table->string('CodiceAttivita')->nullable();
            $table->string('Utente')->nullable();
            $table->string('CodCliente')->nullable();
            $table->string('CodUser')->nullable();
            $table->string('UtenteMod')->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->string('Mansione')->nullable();
            $table->string('TipoContrSimulatore')->nullable();

            $table->timestamps(); // Se vuoi le colonne created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giornate');
    }
};

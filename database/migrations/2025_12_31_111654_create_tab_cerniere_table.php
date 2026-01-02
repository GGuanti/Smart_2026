<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_cerniere', function (Blueprint $table) {
            // PK (Numerazione automatica Access)
            $table->id('id_col_ferr');

            // Campi Access
            $table->string('des_cernira', 255)->nullable();     // Testo breve
            $table->decimal('importo', 10, 2)->nullable();      // Numerico (prezzo)
            $table->string('filtro_sistema', 50)->nullable();   // Testo breve
            $table->string('collezione', 100)->nullable();      // Testo breve
            $table->string('codice', 50)->nullable();           // Testo breve
            $table->longText('modello')->nullable();            // Testo lungo
            $table->string('col_stampa', 100)->nullable();      // Testo breve

            $table->timestamps();

            // Indici utili (se filtri spesso)
            $table->index('filtro_sistema');
            $table->index('codice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_cerniere');
    }
};

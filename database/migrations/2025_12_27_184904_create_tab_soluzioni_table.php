<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_soluzioni', function (Blueprint $table) {
            // IdTabSoluzioni (Autonumber)
            $table->bigIncrements('id_tab_soluzioni');

            // Testo breve
            $table->string('tipologia')->nullable();
            $table->string('soluzione')->nullable();
            $table->string('ass_collistino')->nullable();
            $table->string('immagine')->nullable();
            $table->string('img_soluzioni')->nullable();
            $table->string('des_stampa')->nullable();
            $table->string('fasci_mant')->nullable();
            $table->string('cod_porta')->nullable();

            // Numerico (Access spesso = Intero lungo)
            $table->integer('nante')->nullable();
            $table->integer('filtro_serr')->nullable();

            // Numerico (costi/maggiorazioni: meglio decimal)
            $table->decimal('costo_montaggio', 10, 2)->nullable();
            $table->decimal('magg_fasc_mant', 10, 2)->nullable();

            $table->timestamps();

            // opzionale: indice se filtri spesso per CodPorta o Tipologia
            $table->index('cod_porta');
            $table->index('tipologia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_soluzioni');
    }
};

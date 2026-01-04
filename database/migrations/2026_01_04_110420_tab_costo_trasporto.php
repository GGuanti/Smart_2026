<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_costo_trasporto', function (Blueprint $table) {
            $table->id(); // Id AUTOINCREMENT
            $table->string('regione', 50); // Regione
            $table->double('min_tass');    // MinTass
            $table->double('costo');       // Costo

            // opzionale ma consigliato
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_costo_trasporto');
    }
};

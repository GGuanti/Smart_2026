<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elenchi_contratti', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Tipo_di_contratto')->nullable();
            $table->string('Lettera')->nullable();
            $table->string('FiltroGiornate')->nullable();
            $table->string('UniLav')->nullable();
            $table->string('CCNL')->nullable();
            $table->string('LivelloInquadramento')->nullable();
            $table->string('CodPatINAIL')->nullable();

            $table->timestamps(); // created_at / updated_at opzionali
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elenchi_contratti');
    }
};

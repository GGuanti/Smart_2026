<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elenco_contratti', function (Blueprint $table) {
            $table->increments('IdDoc'); // chiave primaria int autoincrementale

            $table->string('NomeDoc')->nullable();
            $table->string('NomeDot')->nullable();

            $table->timestamps(); // opzionale: created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elenco_contratti');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_serratura', function (Blueprint $table) {
            // PK (Numerazione automatica Access)
            $table->id('id_serratura');

            // Campi Access
            $table->string('des_serratura', 255)->nullable();
            $table->decimal('importo', 10, 2)->nullable();
            $table->integer('filtro_sistema')->nullable();
            $table->string('codice', 50)->nullable();
            $table->string('codice2', 50)->nullable();

            // Timestamp Laravel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_serratura');
    }
};

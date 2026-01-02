<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ass_mod_vetri', function (Blueprint $table) {
            // PK (equivalente a Numerazione automatica)
            $table->id();

            // Campi Access
            $table->string('colonna_list_vetro', 100)->nullable();
            $table->string('des_modello', 100)->nullable();

            // Timestamp Laravel (opzionali ma consigliati)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ass_mod_vetri');
    }
};

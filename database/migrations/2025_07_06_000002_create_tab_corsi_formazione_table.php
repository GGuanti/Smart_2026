<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTabCorsiFormazioneTable extends Migration
{
    public function up(): void
    {
        Schema::create('TabCorsiFormazione', function (Blueprint $table) {
            $table->id('IdCorso'); // Numerazione automatica
            $table->string('TipoCorso', 255)->nullable(); // Testo breve
            $table->float('DurataAttestato')->nullable(); // Numerico
            $table->float('Contributo')->nullable(); // Numerico

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TabCorsiFormazione');
    }
}

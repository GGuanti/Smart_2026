<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_visite_mediche', function (Blueprint $table) {
            $table->integer('IdVisita', true, false);
            $table->string('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->dateTime('DataVisita')->nullable();
            $table->dateTime('DataScadenza')->nullable();
            $table->string('CodCliente', 7)->nullable();
            // Indici (se servono)
            $table->index('CodCliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_visite_mediche');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finitura_telaio', function (Blueprint $table) {
            $table->id('IdFinTelaio');

            $table->string('Tipologia', 50);
            $table->string('Colore', 50);
            $table->string('FiltroTipoTel', 50)->nullable();

            $table->integer('Campo1')->nullable();
            $table->integer('Campo2')->nullable();

            $table->string('Cod1', 50)->nullable();
            $table->string('Cod2', 50)->nullable();
            $table->string('Descr', 255)->nullable();
            $table->string('CodDist', 50)->nullable();
            $table->string('ColKitRt', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finitura_telaio');
    }
};

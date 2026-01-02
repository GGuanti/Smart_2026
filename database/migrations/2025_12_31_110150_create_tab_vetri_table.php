<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_vetri', function (Blueprint $table) {
            // PK
            $table->id('id_vetro');

            // Dati base
            $table->string('cod_art', 50)->nullable();
            $table->string('des_vetro', 255)->nullable();

            // Costi
            $table->decimal('cst_mq', 10, 2)->nullable();
            $table->decimal('cst_fisso', 10, 2)->nullable();

            // Dimensioni / flag
            $table->string('porta', 20)->nullable();
            $table->decimal('sup', 10, 2)->nullable();
            $table->decimal('a', 10, 2)->nullable();
            $table->decimal('tutta_h', 10, 2)->nullable();

            // Campi lettera (Access)
            $table->string('b', 20)->nullable();
            $table->string('c', 20)->nullable();
            $table->string('d', 20)->nullable();
            $table->string('e', 20)->nullable();
            $table->string('f', 20)->nullable();
            $table->string('g', 20)->nullable();
            $table->string('h', 20)->nullable();
            $table->string('h1', 20)->nullable();
            $table->string('i', 20)->nullable();
            $table->string('l', 20)->nullable();
            $table->string('m', 20)->nullable();
            $table->string('n', 20)->nullable();
            $table->string('o', 20)->nullable();
            $table->string('p', 20)->nullable();
            $table->string('q', 20)->nullable();
            $table->string('r', 20)->nullable();
            $table->string('s', 20)->nullable();
            $table->string('t', 20)->nullable();
            $table->string('u', 20)->nullable();
            $table->string('v', 20)->nullable();
            $table->string('z', 20)->nullable();
            $table->string('aa', 20)->nullable();

            // Altri
            $table->string('fascia_vetro', 50)->nullable();
            $table->integer('corr_vetro')->nullable();

            // Timestamps Laravel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_vetri');
    }
};

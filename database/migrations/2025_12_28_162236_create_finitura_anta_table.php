<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finitura_anta', function (Blueprint $table) {
            $table->id('IdFinAnta');

            $table->string('Tipologia', 50);
            $table->string('Colore', 50);

            $table->integer('MaggAnta')->nullable();

            $table->string('Cod1', 50)->nullable();
            $table->string('Cod2', 50)->nullable();
            $table->string('Descr', 255)->nullable();
            $table->string('CodDist', 50)->nullable();

            $table->boolean('Espr1')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finitura_anta');
    }
};

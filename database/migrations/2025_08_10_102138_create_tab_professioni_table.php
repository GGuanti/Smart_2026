<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('tab_professioni', function (Blueprint $table) {
            $table->bigIncrements('id');                 // Autonumerico
            $table->string('Professione', 150);          // Testo breve
            $table->string('UniLav', 100)->nullable();   // Testo breve
            $table->string('LivelloCCNL', 50)->nullable();
            $table->decimal('Minima', 10, 2)->nullable();// Numerico
            $table->string('CodUniLav', 50)->nullable(); // Testo breve
            $table->string('Settore', 150)->nullable();  // Testo breve
            // niente timestamps
        });
    }
    public function down(): void {
        Schema::dropIfExists('tab_professioni');
    }
};

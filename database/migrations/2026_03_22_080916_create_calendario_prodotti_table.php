<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('calendario_prodotti', function (Blueprint $table) {
    $table->id();

    // 🔥 CHIAVE GIUSTA
    $table->foreignId('calendario_id')
        ->constrained('calendario_isomax')
        ->cascadeOnDelete();

    $table->string('Prodotto')->nullable();
    $table->integer('Pezzi')->default(0);

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_prodotti');
    }
};

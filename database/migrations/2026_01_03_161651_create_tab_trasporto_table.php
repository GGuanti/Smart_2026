<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_trasporto', function (Blueprint $table) {
            $table->id(); // ID (auto increment)
            $table->string('des', 150); // Descrizione trasporto
            $table->string('codts', 20)->nullable(); // Codice trasporto
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_trasporto');
    }
};

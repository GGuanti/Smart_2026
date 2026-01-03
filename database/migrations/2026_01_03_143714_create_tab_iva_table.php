<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tab_iva', function (Blueprint $table) {
            $table->id();
            $table->string('des', 255);
            $table->decimal('valore', 6, 2);
            $table->string('cod_iva', 20)->unique();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tab_iva');
    }
};

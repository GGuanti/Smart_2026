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
    Schema::create('accessori', function (Blueprint $table) {
        $table->id(); // IdAccessori

        $table->string('cod_art')->nullable();
        $table->string('des_accessori')->nullable();
        $table->string('codice_metodo')->nullable();

        $table->decimal('importo', 10, 2)->default(0);

        $table->boolean('vis_dim')->default(false);
        $table->boolean('nascondi')->default(false);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessori');
    }
};

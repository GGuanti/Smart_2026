<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_imbotte', function (Blueprint $table) {
            $table->bigIncrements('id_imbotte');

            $table->string('des_imbotte')->nullable();

            $table->decimal('importo', 10, 2)->default(0);

            $table->string('filtro_sistema')->nullable();
            $table->string('filtro_tipo_telaio')->nullable();

            $table->integer('spess_muro_da')->nullable();
            $table->integer('spess_muro_a')->nullable();

            $table->string('filtro_imbotte')->nullable();
            $table->string('img')->nullable();

            $table->decimal('importo_ral', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_imbotte');
    }
};

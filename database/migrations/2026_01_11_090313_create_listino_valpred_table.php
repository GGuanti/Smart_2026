<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listino_valpred', function (Blueprint $table) {
            $table->id();

            // modello
            $table->unsignedInteger('id_listino');

            // soluzione (tab_soluzioni.id_tab_soluzioni)
            $table->unsignedInteger('id_tab_soluzioni');

            // valori predefiniti
            $table->json('valpred')->nullable();

            $table->timestamps();

            // unicità per modello + soluzione
            $table->unique(
                ['id_listino', 'id_tab_soluzioni'],
                'uq_listino_soluzione'
            );

            // 🔗 FK (se vuoi integrità referenziale)
            $table->foreign('id_listino')
                ->references('id_listino')
                ->on('listini')
                ->cascadeOnDelete();

            $table->foreign('id_tab_soluzioni')
                ->references('id_tab_soluzioni')
                ->on('tab_soluzioni')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listino_valpred');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_items', function (Blueprint $table) {
            $table->id();

            // FK verso appointments.Nordine (NON verso id)
            $table->unsignedBigInteger('Nordine');

            $table->string('Prodotto');
            $table->string('Colore')->nullable();
            $table->unsignedInteger('Pezzi')->default(0);

            $table->boolean('Taglio')->default(false);
            $table->boolean('Assemblaggio')->default(false);
            $table->boolean('Comandi')->default(false);
            $table->boolean('TaglioZoccolo')->default(false);
            $table->boolean('TaglioLamelle')->default(false);
            $table->boolean('MontaggioLamelle')->default(false);

            $table->text('Descrizione')->nullable();

            $table->timestamps();

            // indice + FK
            $table->index('Nordine');
            $table->foreign('Nordine')
                ->references('Nordine')
                ->on('appointments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_items');
    }
};

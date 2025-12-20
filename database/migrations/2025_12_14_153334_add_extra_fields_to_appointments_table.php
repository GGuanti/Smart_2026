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
        Schema::table('appointments', function (Blueprint $table) {
            // Testo breve
            // Data/ora
            $table->dateTime('DataConferma')->nullable();;
            $table->dateTime('DataConsegna')->nullable();;
            $table->string('Colore', 30);
            // Numerico
            $table->unsignedInteger('Pezzi');
            // Si/No
            $table->boolean('T');
            $table->boolean('Tz');
            $table->boolean('TL');
            $table->boolean('A');
            $table->boolean('C');
            $table->boolean('L');
            // Testo breve (note)
            $table->string('Annotazioni', 255);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'DataConferma',
                'DataConsegna',
                'Colore',
                'Pezzi',
                'T', 'Tz', 'TL', 'A', 'C', 'L',
                'Annotazioni',
            ]);
        });
    }
};

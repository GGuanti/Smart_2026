<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTabCorsiFormazioneUserTable extends Migration
{
    public function up(): void
    {
        Schema::create('TabCorsiFormazioneUser', function (Blueprint $table) {
            $table->id('IdTabCorso'); // IDENTITY(1,1)
            $table->float('IdCorso')->nullable();
            $table->string('CodCliente', 7)->nullable();
            $table->string('Stato', 255)->nullable();
            $table->date('DataAttestato')->nullable();
            $table->char('UtenteMod', 50)->nullable();
            $table->dateTime('DataModifica')->nullable();
            $table->char('Note', 70)->nullable();

            $table->primary('IdTabCorso', 'PK_TabCorsiFormazioneUser');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('TabCorsiFormazioneUser');
    }
}

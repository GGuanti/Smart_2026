<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_elementi_ordine', function (Blueprint $table) {
            $table->increments('Id'); // int identity PK

            // collegamento ordine (chiave umana)
            $table->integer('Nordine')->nullable();
            $table->index('Nordine');

            // dimensioni / quantità
            $table->double('DimL')->nullable();
            $table->double('DimA')->nullable();
            $table->double('DimSp')->nullable();
            $table->double('Qta')->nullable();

            // prezzi
            $table->double('PrezzoCad')->nullable();
            $table->double('PrezzoMan')->nullable();

            // note / file
            $table->string('NoteMan', 255)->nullable();
            $table->string('PercFile', 255)->nullable();

            // riferimenti listini / opzioni (come da DB originale)
            $table->integer('IdModello')->nullable();
            $table->double('IdColAnta')->nullable();
            $table->double('IdColTelaio')->nullable();
            $table->double('IdSoluzione')->nullable();
            $table->double('IdManiglia')->nullable();
            $table->double('IdApertura')->nullable();
            $table->double('IdTipTelaio')->nullable();
            $table->double('IdVetro')->nullable();
            $table->double('IdColFerr')->nullable();
            $table->double('IdSerratura')->nullable();

            // flag / accessori
            $table->string('CkTaglioObl', 255)->nullable();
            $table->double('IdImbotte')->nullable();

            // testo fisso
            $table->char('TxtCassMet', 255)->nullable(); // nchar(255)

            // opzionale: timestamps se vuoi tracciarli
            // $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_elementi_ordine');
    }
};

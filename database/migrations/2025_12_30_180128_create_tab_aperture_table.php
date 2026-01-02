<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_aperture', function (Blueprint $table) {
            $table->bigIncrements('IdApertura');
            $table->string('Tipologia', 191)->nullable();
            $table->string('Des', 191)->nullable();
            $table->string('Immagine', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_aperture');
    }
};

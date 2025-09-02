<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('allegati', function (Blueprint $table) {
            $table->id();
            $table->string('id_prog');            // string perché IdProg nel tuo form è string
            $table->string('nome');               // nome originale
            $table->string('path');               // es: allegati/1234/file.pdf
            $table->string('mime', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index('id_prog');
        });
    }
    public function down(): void {
        Schema::dropIfExists('allegati');
    }
};

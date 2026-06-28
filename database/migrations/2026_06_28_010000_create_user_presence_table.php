<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('slot'); // arrotondato a 5 minuti
            $table->timestamps();
            $table->unique(['user_id', 'slot']); // 1 riga per utente per slot
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_presence');
    }
};

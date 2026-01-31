<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('door_configs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      // tipo apertura
      $table->string('opening_type');
      // battente | libro | rototraslante | scorrevole_interno | scorrevole_esterno | scorrevole_mantovana

      // riferimenti texture
      $table->foreignId('anta_texture_id')->nullable()->constrained('textures')->nullOnDelete();
      $table->foreignId('telaio_texture_id')->nullable()->constrained('textures')->nullOnDelete();

      // parametri configurazione (JSON)
      $table->json('params')->nullable();

      $table->string('name')->nullable(); // nome configurazione
      $table->timestamps();
    });
  }

  public function down(): void { Schema::dropIfExists('door_configs'); }
};

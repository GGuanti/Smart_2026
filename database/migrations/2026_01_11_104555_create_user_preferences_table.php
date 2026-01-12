<?php

// database/migrations/xxxx_xx_xx_create_user_preferences_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_preferences', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('key', 190);
      $table->json('value')->nullable();
      $table->timestamps();

      $table->unique(['user_id', 'key']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('user_preferences');
  }
};

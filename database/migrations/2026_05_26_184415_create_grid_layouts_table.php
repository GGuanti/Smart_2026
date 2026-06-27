<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grid_layouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('query_name');

            // Esempio:
            // [
            //   {
            //     "key": "name",
            //     "label": "Nome",
            //     "width": 200,
            //     "visible": true,
            //     "order": 1
            //   }
            // ]
            $table->json('layout');

            $table->timestamps();

            $table->unique(['user_id', 'query_name']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grid_layouts');
    }
};

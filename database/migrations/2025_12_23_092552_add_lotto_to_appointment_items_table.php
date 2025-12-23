<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->string('Lotto', 50)
                  ->nullable()
                  ->after('pezzi'); // cambia se vuoi un'altra posizione
        });
    }

    public function down(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->dropColumn('Lotto');
        });
    }
};

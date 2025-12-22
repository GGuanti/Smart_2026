<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->boolean('OrdineVetri')
                  ->default(false)
                  ->after('Fermavetri');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->dropColumn('OrdineVetri');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->boolean('Accessori')->default(false)->after('vetratura');
            $table->boolean('Coprifili')->default(false)->after('Accessori');
            $table->boolean('Fermavetri')->default(false)->after('Coprifili');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_items', function (Blueprint $table) {
            $table->dropColumn(['Accessori', 'Coprifili', 'Fermavetri']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
Schema::table('tab_elementi_ordine', function (Blueprint $table) {
    $table->json('accessori_sel')->nullable(); // niente default
});
    }

    public function down(): void
    {
        Schema::table('tab_elementi_ordine', function (Blueprint $table) {
            $table->dropColumn('accessori_sel');
        });
    }
};

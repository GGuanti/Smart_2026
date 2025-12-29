<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tab_ordine', function (Blueprint $table) {
            // se vuoi obbligatorio: togli nullable()
            $table->foreignId('user_id')
                ->nullable()
                ->after('ID')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tab_ordine', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id'); // droppa FK + colonna
        });
    }
};

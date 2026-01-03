<?php
// php artisan make:migration add_valpred_to_listini_table --table=listini

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listini', function (Blueprint $table) {
            $table->json('ValPred')->nullable()->after('maniglie'); // scegli tu "after"
        });
    }

    public function down(): void
    {
        Schema::table('listini', function (Blueprint $table) {
            $table->dropColumn('ValPred');
        });
    }
};


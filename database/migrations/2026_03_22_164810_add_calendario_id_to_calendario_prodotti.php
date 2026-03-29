<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('calendario_prodotti', function (Blueprint $table) {
        $table->unsignedBigInteger('calendario_id')->nullable()->after('id');

        $table->foreign('calendario_id')
            ->references('id')
            ->on('calendario_isomax')
            ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendario_prodotti', function (Blueprint $table) {
            //
        });
    }
};

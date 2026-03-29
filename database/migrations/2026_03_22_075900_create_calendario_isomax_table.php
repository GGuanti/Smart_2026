<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('calendario_isomax', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->dateTime('DataInizio');
    $table->dateTime('DataFine')->nullable();
    $table->integer('Nordine')->nullable();
    $table->integer('Pezzi')->default(0);
    $table->string('status')->default('Da Pianificare');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_isomax');
    }
};

<?php
// database/migrations/2025_01_01_000000_create_disegni_dxf_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('DisegniDXF', function (Blueprint $table) {
            $table->bigIncrements('IdRigaDXF');        // PK
            $table->string('Codice', 100)->index();
            $table->string('Descrizione', 255)->nullable();
            $table->integer('LRG')->nullable();        // larghezza
            $table->integer('ALT')->nullable();        // altezza
            $table->longText('Dxf');                   // contenuto DXF (testo)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DisegniDXF');
    }
};

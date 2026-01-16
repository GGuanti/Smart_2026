<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_replica')->create('sync_inbox', function (Blueprint $table) {

            $table->bigIncrements('id');

            // stesso UUID che sta in sync_outbox.event_id
            $table->uuid('event_id')->unique();

            // info utili per debug/analisi
            $table->string('type', 100);                 // es: ordini.upsert
            $table->string('aggregate_type', 50);        // es: Ordine
            $table->string('aggregate_id', 100);         // es: Nordine o ID

            // opzionale: hash del payload (utile per audit)
            $table->char('payload_hash', 64)->nullable(); // sha256

            $table->timestamps();

            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_replica')->dropIfExists('sync_inbox');
    }
};

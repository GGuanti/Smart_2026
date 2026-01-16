<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_main')->create('sync_outbox', function (Blueprint $table) {

            $table->bigIncrements('id');

            // ID globale evento (fondamentale per idempotenza su DB2)
            $table->uuid('event_id')->unique();

            // Entità di riferimento (es: Ordine, Appointment, NotaSpesa)
            $table->string('aggregate_type', 50);
            $table->string('aggregate_id', 100);

            // Tipo evento (es: ordini.upsert, ordini.deleted, appointments.moved)
            $table->string('type', 100);

            // Payload JSON (dati da applicare su DB2)
            $table->json('payload');

            // Stato sincronizzazione
            $table->enum('status', [
                'pending',
                'processing',
                'synced',
                'failed',
            ])->default('pending');

            // Retry / diagnostica
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();      // per backoff manuale
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Indici per performance (fondamentali con molti eventi)
            $table->index(['status', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_main')->dropIfExists('sync_outbox');
    }
};

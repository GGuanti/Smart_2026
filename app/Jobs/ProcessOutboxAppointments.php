<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // ✅ corretto
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessOutboxAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;

    public array $backoff = [10, 30, 60, 120, 300];

    public function handle(): void
    {
        $rows = DB::connection('mysql_main')->table('sync_outbox')
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->whereIn('type', ['appointments.upsert', 'appointments.delete'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($rows as $row) {
            $this->processOne($row);
        }
    }

    private function processOne(object $row): void
    {
        // ✅ lock atomico: prendo la riga solo se è ancora pending
        $updated = DB::connection('mysql_main')->table('sync_outbox')
            ->where('id', $row->id)
            ->where('status', 'pending')
            ->update([
                'status'          => 'processing',
                'attempts'        => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
                'updated_at'      => now(),
            ]);

        if ($updated === 0) {
            return; // già presa da un altro worker
        }

        $payload = $row->payload ? json_decode($row->payload, true) : null;

        if (!is_array($payload)) {
            DB::connection('mysql_main')->table('sync_outbox')->where('id', $row->id)->update([
                'status'     => 'pending',
                'last_error' => 'Payload JSON nullo o non valido',
                'updated_at' => now(),
            ]);
            return;
        }

        try {
            // ✅ idempotenza: se event già applicato su replica => chiudi subito
            $already = DB::connection('mysql_replica')->table('sync_inbox')
                ->where('event_id', $row->event_id)
                ->exists();

            if ($already) {
                DB::connection('mysql_main')->table('sync_outbox')->where('id', $row->id)->update([
                    'status'     => 'synced',
                    'last_error' => null,
                    'updated_at' => now(),
                ]);
                return;
            }

            DB::connection('mysql_replica')->transaction(function () use ($row, $payload) {

                $key  = $payload['key'] ?? [];
                $data = $payload['data'] ?? [];

                if (!is_array($key))  $key  = [];
                if (!is_array($data)) $data = [];

                // -----------------------------
                // JSON fields (Prodotto è JSON)
                // -----------------------------
                if (array_key_exists('Prodotto', $data) && is_array($data['Prodotto'])) {
                    $data['Prodotto'] = json_encode($data['Prodotto'], JSON_UNESCAPED_UNICODE);
                }

                // -----------------------------
                // DATETIME: ISO8601 -> MySQL
                // (2026-01-06T00:00:00.000000Z -> 2026-01-06 00:00:00)
                // -----------------------------
                foreach (['DataInizio', 'DataFine', 'DataConferma', 'DataConsegna'] as $col) {
                    if (!array_key_exists($col, $data) || empty($data[$col])) {
                        continue;
                    }

                    try {
                        $data[$col] = Carbon::parse($data[$col])
                            ->setTimezone('Europe/Rome')
                            ->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $data[$col] = null; // mai crashare per una data
                    }
                }

                // -----------------------------
                // APPLY EVENT
                // -----------------------------
                if ($row->type === 'appointments.upsert') {

                    DB::connection('mysql_replica')->table('appointments')->updateOrInsert(
                        $key,
                        $data // ✅ usa $data normalizzato
                    );

                } elseif ($row->type === 'appointments.delete') {

                    if (!empty($key)) {
                        DB::connection('mysql_replica')->table('appointments')
                            ->where($key)
                            ->delete();
                    }
                }

                // ✅ registra l’evento applicato (idempotenza)
                DB::connection('mysql_replica')->table('sync_inbox')->insert([
                    'event_id'       => $row->event_id,
                    'type'           => $row->type,
                    'aggregate_type' => $row->aggregate_type,
                    'aggregate_id'   => $row->aggregate_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            // ✅ ok finale
            DB::connection('mysql_main')->table('sync_outbox')->where('id', $row->id)->update([
                'status'     => 'synced',
                'last_error' => null,
                'updated_at' => now(),
            ]);

        } catch (\Throwable $e) {

            DB::connection('mysql_main')->table('sync_outbox')->where('id', $row->id)->update([
                'status'       => 'pending',
                'available_at' => now()->addSeconds(60),
                'last_error'   => substr($e->getMessage(), 0, 5000),
                'updated_at'   => now(),
            ]);

            throw $e;
        }
    }
}

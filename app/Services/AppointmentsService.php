<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentsService
{
    public function create(array $data): int
    {
        return DB::connection('mysql_main')->transaction(function () use ($data) {

            $id = DB::connection('mysql_main')->table('appointments')->insertGetId($data);

            DB::connection('mysql_main')->table('sync_outbox')->insert([
                'event_id' => (string) Str::uuid(),
                'aggregate_type' => 'Appointment',
                'aggregate_id' => (string) $id,
                'type' => 'appointments.upsert',
                'payload' => json_encode([
                    'key'  => ['id' => $id],
                    'data' => array_merge($data, ['id' => $id]),
                ]),
                'status' => 'pending',
                'available_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });
    }

    public function update(int $id, array $data): void
    {
        DB::connection('mysql_main')->transaction(function () use ($id, $data) {

            DB::connection('mysql_main')->table('appointments')
                ->where('id', $id)
                ->update($data);

            DB::connection('mysql_main')->table('sync_outbox')->insert([
                'event_id' => (string) Str::uuid(),
                'aggregate_type' => 'Appointment',
                'aggregate_id' => (string) $id,
                'type' => 'appointments.upsert',
                'payload' => json_encode([
                    'key'  => ['id' => $id],
                    'data' => array_merge($data, ['id' => $id]),
                ]),
                'status' => 'pending',
                'available_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function delete(int $id): void
    {
        DB::connection('mysql_main')->transaction(function () use ($id) {

            DB::connection('mysql_main')->table('appointments')->where('id', $id)->delete();

            DB::connection('mysql_main')->table('sync_outbox')->insert([
                'event_id' => (string) Str::uuid(),
                'aggregate_type' => 'Appointment',
                'aggregate_id' => (string) $id,
                'type' => 'appointments.delete',
                'payload' => json_encode(['key' => ['id' => $id]]),
                'status' => 'pending',
                'available_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}

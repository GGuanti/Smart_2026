<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarioIsomax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarioController extends Controller
{
    /**
     * Aggiorna Data Fine Produzione tramite NOrdine
     * Chiamata da VBA / Access / API esterne
     *
     * URL:
     * PUT /api/appointments/{Nordine}
     */

    public function updateByOrdine(Request $request, $Nordine)
    {
        try {

            $Nordine = trim((string)$Nordine);

            /*
        |--------------------------------------------------------------------------
        | APPOINTMENT
        |--------------------------------------------------------------------------
        */
            DB::table('appointments')
                ->updateOrInsert(

                    [
                        'Nordine' => $Nordine,
                    ],

                    [
 'user_id' => 1,
                        'title' =>
                        $request->input('title'),

                        'description' =>
                        $request->input('description'),

                        'DataInizio' =>
                        $request->input('DataInizio'),

                        'DataFine' =>
                        $request->input('DataFine'),

                        'DataConferma' =>
                        $request->input('DataConferma'),

                        'DataConsegna' =>
                        $request->input('DataConsegna'),

                        'status' =>
                        $request->input('status', 'Completato'),

                        'StatoMagazzino' =>
                        $request->input('StatoMagazzino'),

                        'Riferimento' =>
                        $request->input('Riferimento'),

                        'Colore' =>
                        $request->input('Colore'),

                        'Annotazioni' =>
                        $request->input('Annotazioni'),

                        'Pezzi' =>
                        $request->input('Pezzi', 0),

                        'updated_at' => now(),

                        'created_at' => now(),
                    ]
                );

            /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */
            if ($request->has('items')) {

                foreach ($request->items as $item) {

                    DB::table('appointment_items')
                        ->updateOrInsert(

                            [
                                'Nordine' => $Nordine,

                                'Prodotto' =>
                                $item['prodotto'] ?? '',
                            ],

                            [

                                'Descrizione' =>
                                $item['descrizione'] ?? null,

                                'Colore' =>
                                $item['colore'] ?? null,

                                'Pezzi' =>
                                $item['pezzi'] ?? 0,

                                'Lotto' =>
                                $item['Lotto'] ?? null,

                                'Taglio' =>
                                !empty($item['taglio']) ? 1 : 0,

                                'Assemblaggio' =>
                                !empty($item['assemblaggio']) ? 1 : 0,

                                'operatore_assemblaggio' =>
                                $item['operatore_assemblaggio'] ?? null,

                                'Comandi' =>
                                !empty($item['comandi']) ? 1 : 0,

                                'TaglioZoccolo' =>
                                !empty($item['taglio_zoccolo']) ? 1 : 0,

                                'TaglioLamelle' =>
                                !empty($item['taglio_lamelle']) ? 1 : 0,

                                'MontaggioLamelle' =>
                                !empty($item['montaggio_lamelle']) ? 1 : 0,

                                'Ferramenta' =>
                                !empty($item['Ferramenta']) ? 1 : 0,

                                'Vetratura' =>
                                !empty($item['Vetratura']) ? 1 : 0,

                                'Accessori' =>
                                !empty($item['Accessori']) ? 1 : 0,

                                'Coprifili' =>
                                !empty($item['Coprifili']) ? 1 : 0,

                                'Fermavetri' =>
                                !empty($item['Fermavetri']) ? 1 : 0,

                                'OrdineVetri' =>
                                !empty($item['OrdineVetri']) ? 1 : 0,

                                'updated_at' => now(),

                                'created_at' => now(),
                            ]
                        );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato/creato correttamente',
                'Nordine' => $Nordine
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}

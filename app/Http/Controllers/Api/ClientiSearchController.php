<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ClientiSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        // Adatta alla tua sorgente dati
        $rows = DB::table('clienti')
            ->selectRaw('CodCliente as codice, RagioneSociale as nome')
            ->where(function($w) use ($q) {
                $w->where('RagioneSociale','like',"%$q%")
                  ->orWhere('CodCliente','like',"%$q%");
            })
            ->orderBy('RagioneSociale')
            ->limit(100)
            ->get();

        return response()->json($rows);
    }
}

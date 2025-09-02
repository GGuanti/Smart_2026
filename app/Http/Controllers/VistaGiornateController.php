<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VistaGiornateController extends Controller
{
    public function index(Request $request)
    {
        $idProg = $request->string('IdProg')->trim();
        abort_if($idProg->isEmpty(), 422, 'IdProg è obbligatorio');

        $rows = DB::table('vistagiornate')
            ->select('IdGiornate','IdProg','IDContratto','CodCliente',
                     'A_NomeVisualizzato','Data','Diaria','Retribuzione','CodiceAttivita')
            ->where('IdProg', $idProg->toString())
            ->orderBy('Data')
            ->get();

        return response()->json($rows);
    }
}

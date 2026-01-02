<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Prezzi\PrezzoPortaService;

class PreventiviCalcController extends Controller
{
    public function prezzoCad(Request $request, PrezzoPortaService $svc)
    {
        $data = $request->validate([
            'IdModello'   => 'required|integer',
            'IdColAnta'   => 'required|integer',
            'IdColTelaio' => 'required|integer',
            'IdSoluzione' => 'required|integer',
            'IdManiglia'  => 'required|integer',
            'IdApertura'  => 'required|integer',
            'IdTipTelaio' => 'required|integer',
            'IdVetro'     => 'required|integer',
            'IdColFerr'   => 'required|integer',
            'DimL'        => 'required|integer',
            'DimA'        => 'required|integer',
            'DimSp'       => 'required|integer',
            'IdSerratura' => 'required|integer',
            'CkTaglioObl' => 'required|string', // "Si"/"No"
            'IdImbotte'   => 'required|integer',
            'NANTE'       => 'required|integer',
        ]);

        $prezzoCad = $svc->calcola($data);
        logger()->info('PREZZO CAD INPUT', $request->all());
        return response()->json([
            'PrezzoCad' => $prezzoCad,
        ]);
    }
}

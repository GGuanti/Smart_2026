<?php
// app/Http/Controllers/DxfController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DxfController extends Controller
{
    public function saveSvg(Request $req)
    {
        $data = $req->validate([
            'svg' => 'required|string',   // SVG completo
            'name'=> 'nullable|string'
        ]);

        // PDF da SVG con DomPDF
        $html = '<!doctype html><html><head><meta charset="utf-8">
            <style>@page{margin:10mm} body{font-family:DejaVu Sans, sans-serif}</style>
            </head><body>'.$data['svg'].'</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4','portrait');
        return $pdf->download(($data['name'] ?? 'disegno').'.pdf');
    }
}

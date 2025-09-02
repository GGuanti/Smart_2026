<?php

namespace App\Http\Controllers;

use App\Models\ReportJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportJobController extends Controller
{
    /**
     * Mostra la pagina Inertia per la generazione del report
     */
    public function index()
    {
        return Inertia::render('Report/Index');
    }

    /**
     * Crea un nuovo job di report e restituisce l'id al frontend
     */
    public function genera(Request $request)
    {
     $nomefile = 'report_' . time() . '.pdf';
    //   $nomefile = 'pippo.pdf';
        $job = ReportJob::create([
            'nomefile' => $nomefile,
            'nome_report' => 'Report Prova',
            'filtro' => json_encode($request->all()),
            'eseguito' => false,
        ]);

        // In un sistema reale potresti lanciare qui un job Laravel
        // dispatch(new GeneraReportJob($job));

        return response()->json(['id' => $job->id]);
    }

    /**
     * Verifica se il file PDF è stato generato
     */
    public function check($id)
    {
        $job = ReportJob::findOrFail($id);
        $filePath = storage_path("app/public/reports/{$job->nomefile}");

        if (file_exists($filePath)) {
            $job->update(['eseguito' => true]);

            return response()->json([
                'ready' => true,
                'url'   => asset("storage/reports/{$job->nomefile}"),
            ]);
        }

        return response()->json(['ready' => false]);
    }
}

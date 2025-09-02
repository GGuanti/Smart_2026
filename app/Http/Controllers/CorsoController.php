<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use Inertia\Inertia;

    class CorsoController extends Controller
    {

        public function index(Request $request)
        {
            $codCliente = $request->query('cod'); // es. "C 2797"

            if (!$codCliente) {
                return response()->json(['error' => 'codCliente mancante'], 400);
            }

            $corsi = \DB::table('TabCorsiFormazioneUser')
                ->join('TabCorsiFormazione', 'TabCorsiFormazioneUser.IdCorso', '=', 'TabCorsiFormazione.IdCorso')
                ->where('TabCorsiFormazioneUser.CodCliente', $codCliente)
                ->select([
                    'TabCorsiFormazioneUser.IdTabCorso',
                    'TabCorsiFormazioneUser.CodCliente',
                    'TabCorsiFormazioneUser.DataAttestato',
                    'TabCorsiFormazione.TipoCorso',
                    'TabCorsiFormazione.DurataAttestato',
                    'TabCorsiFormazioneUser.Note',
                    'TabCorsiFormazioneUser.Stato',
                    'TabCorsiFormazioneUser.UtenteMod',
                    'TabCorsiFormazioneUser.DataModifica',
                    'TabCorsiFormazioneUser.IdCorso',
                ])
                ->orderBy('TabCorsiFormazione.TipoCorso')
                ->get();
\Log::info('Corsi restituiti dopo aggiornamento:', ['codCliente' => $codCliente, 'count' => count($corsi)]);
return response()->json($corsi);
            return response()->json($corsi);
        }



        public function store(Request $request)
{
    \Log::info('STORE CORSO', $request->all());

    $data = $request->validate([
        'CodCliente' => 'required|string',
        'IdCorso' => 'required|integer',
        'DataAttestato' => 'nullable|date',
        'Note' => 'nullable|string',
        'Stato' => 'nullable|string',
    ]);

    $data['UtenteMod'] = Auth::user()?->name ?? 'sistema';
    $data['DataModifica'] = now();

    DB::table('TabCorsiFormazioneUser')->insert($data);

    return back()->with('success', 'Corso creato con successo.');
}

public function update(Request $request, $id)
{
    \Log::info('UPDATE CORSO', $request->all());

    $data = $request->validate([
        'CodCliente' => 'required|string',
        'IdCorso' => 'required|integer',
        'DataAttestato' => 'nullable|date',
        'Note' => 'nullable|string',
        'Stato' => 'nullable|string',
    ]);

    $data['UtenteMod'] = Auth::user()?->name ?? 'sistema';
    $data['DataModifica'] = now();

    DB::table('TabCorsiFormazioneUser')
        ->where('IdTabCorso', $id)
        ->update($data);

    return response()->json(['success' => true]);
}

public function destroy($id)
{
    \Log::info('[DELETE CORSO]', ['IdTabCorso' => $id]);

    DB::table('TabCorsiFormazioneUser')->where('IdTabCorso', $id)->delete();

    return response()->json(['success' => true]);
}
    }

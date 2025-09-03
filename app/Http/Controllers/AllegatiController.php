<?php

// app/Http/Controllers/AllegatiController.php
namespace App\Http\Controllers;

use App\Models\Allegato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AllegatiController extends Controller
{
    public function index(string $idProg)
    {
        $rows = Allegato::where('id_prog', $idProg)
            ->orderByDesc('created_at')
            ->get(['id','nome','path','mime','size','created_at']);

        // aggiungi 'url' come accessor dal model
        return response()->json($rows->map(function ($a) {
            return [
                'id'   => $a->id,
                'nome' => $a->nome,
                'url'  => Storage::disk('public')->url($a->path),
                'mime' => $a->mime,
                'size' => $a->size,
                'created_at' => $a->created_at,
            ];
        }));
    }

    public function store(Request $request, string $idProg)
{
    // più robusto: usa mimes (estensioni) oppure tieni i tuoi mimetypes
    $request->validate([
        'file' => ['required','file','mimes:jpg,jpeg,pdf','max:20480'], // 20 MB
    ], [
        'file.mimes' => 'Sono ammessi solo file JPG o PDF.',
    ]);

    $file = $request->file('file');

    // cartella: storage/app/public/allegati/{IdProg}
    $folder = 'allegati/' . trim($idProg);

    // nome pulito + timestamp
    $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $ext  = $file->getClientOriginalExtension();
    $safe = Str::slug($base) ?: 'file';
    $name = $safe . '-' . now()->format('YmdHis') . '.' . strtolower($ext);

    // ✅ salva nel DISK public (=> storage/app/public/...)
    $path = $file->storeAs($folder, $name, 'public');

    // salva record DB
    $al = Allegato::create([
        'id_prog' => $idProg,
        'nome'    => $file->getClientOriginalName(),
        'path'    => $path,                      // es. "allegati/45998/file.pdf"
        'mime'    => $file->getClientMimeType(), // "application/pdf"
        'size'    => $file->getSize(),           // bytes
    ]);

    // ✅ URL web relativo: /storage/allegati/...
    $relativeUrl = Storage::disk('public')->url($path);

    // ✅ URL assoluto con host/porta presi da APP_URL
    $absoluteUrl = url($relativeUrl);

    // ritorna JSON per l’AJAX (axios)
    return response()->json([
        'id'         => $al->id,          // o $al->getKey()
        'nome'       => $al->nome,
        'url'        => $absoluteUrl,     // es. http://127.0.0.1:8001/storage/...
        'mime'       => $al->mime,
        'size'       => $al->size,
        'created_at' => $al->created_at?->toDateTimeString(),
    ], 201);
}


    public function download(Allegato $allegato)
    {
        return Storage::disk('public')->download($allegato->path, $allegato->nome);
    }

    public function destroy(Allegato $allegato)
    {
        Storage::disk('public')->delete($allegato->path);
        $allegato->delete();
        return back(); // per Inertia router.delete
    }
}

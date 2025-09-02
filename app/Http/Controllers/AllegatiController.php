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
        $request->validate([
            'file' => ['required','file','mimetypes:image/jpeg,application/pdf','max:20480'], // 20MB
        ], [
            'file.mimetypes' => 'Sono ammessi solo file JPG o PDF.',
        ]);

        $file = $request->file('file');

        // cartella: allegati/{IdProg}
        $folder = 'allegati/' . trim($idProg);
        // nome pulito + univoco
        $base   = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext    = $file->getClientOriginalExtension();
        $safe   = Str::slug($base) ?: 'file';
        $name   = $safe . '-' . now()->format('YmdHis') . '.' . strtolower($ext);

        $path = $file->storeAs($folder, $name, 'public');

        $al = Allegato::create([
            'id_prog' => $idProg,
            'nome'    => $file->getClientOriginalName(),
            'path'    => $path,
            'mime'    => $file->getClientMimeType(),
            'size'    => $file->getSize(),
        ]);

        return response()->json([
            'id'   => $al->id,
            'nome' => $al->nome,
            'url'  => Storage::disk('public')->url($al->path),
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

<?php

namespace App\Http\Controllers;

use App\Models\Allegato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AllegatiController extends Controller
{
    public function index($idProg)
    {
        try {
            $items = Allegato::where('id_prog', $idProg)
                ->latest()
                ->get()
                ->map(fn ($al) => [
                    'id'         => $al->id,
                    'nome'       => $al->nome,
                    'mime'       => $al->mime,
                    'size'       => $al->size,
                    'created_at' => optional($al->created_at)->toDateTimeString(),
                    'url'        => route('allegati.show', $al),
                ]);

            return response()->json($items);
        } catch (\Throwable $e) {
            Log::error('allegati.index error', ['e' => $e]);
            return response()->json(['message' => 'Errore caricamento allegati'], 500);
        }
    }

    public function show(Allegato $allegato)
    {
        abort_unless(Storage::disk('public')->exists($allegato->path), 404);
        return Storage::disk('public')->response($allegato->path, $allegato->nome);
    }

    public function store(Request $request, $idProg)
    {
        $request->validate([
            'file' => ['required','file','mimetypes:image/jpeg,application/pdf','max:20480'],
        ], ['file.mimetypes' => 'Sono ammessi solo file JPG o PDF.']);

        $file   = $request->file('file');
        $folder = 'allegati/' . trim($idProg);

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = (Str::slug($base) ?: 'file') . '-' . now()->format('YmdHis') . '.' . $ext;

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
            'url'  => route('allegati.show', $al),
        ], 201);
    }

    public function destroy(Allegato $allegato)
    {
        Storage::disk('public')->delete($allegato->path);
        $allegato->delete();
        return back();
    }
}

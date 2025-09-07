<?php

namespace App\Http\Controllers;

use App\Models\Allegato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\StorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllegatiController extends Controller
{
    public function __construct(protected StorageService $storage) {}

    /** Elenco allegati per progetto (idProg) */
    public function index(string $idProg)
{
    $items = Allegato::where('id_prog', $idProg)->latest()->get()->map(fn ($al) => [
        'id'         => $al->id,
        'nome'       => $al->nome,
        'mime'       => $al->mime,
        'size'       => $al->size,
        'created_at' => optional($al->created_at)->toDateTimeString(),
        'url'        => route('allegati.show', $al), // /allegato/{id}
    ]);

    return response()->json($items);
}
    /** Stream/preview di un singolo allegato (by ID allegato) */
    public function show(Allegato $allegato)
    {
        $disk = $allegato->disk ?: config('filesystems.default', 'dropbox');
        $path = $allegato->path;

        if (!$path || !Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $name = $allegato->nome ?: basename($path);
        return $this->storage->streamResponse($path, $name, inline: true, disk: $disk);
    }


    /** Upload file in allegati/{idProg} sul disco scelto (default dropbox) */
    public function store(Request $request, string $idProg)
{
    $request->validate([
        'file' => ['required','file','mimetypes:image/jpeg,application/pdf','max:20480'],
    ], ['file.mimetypes' => 'Sono ammessi solo file JPG o PDF.']);

    $disk   = config('filesystems.default', 'dropbox');
    $file   = $request->file('file');
    $folder = 'allegati/' . trim($idProg);

    $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $ext  = strtolower($file->getClientOriginalExtension());
    $name = (Str::slug($base) ?: 'file') . '-' . now()->format('YmdHis') . '.' . $ext;

    $path = $file->storeAs($folder, $name, $disk);

    $al = Allegato::create([
        'id_prog' => $idProg,
        'nome'    => $file->getClientOriginalName(),
        'path'    => $path,
        'mime'    => $file->getClientMimeType(),
        'size'    => $file->getSize(),
        'disk'    => $disk,               // ✅ importante
    ]);

    return response()->json([
        'id'   => $al->id,
        'nome' => $al->nome,
        'url'  => route('allegati.show', $al), // ora punta a /allegato/{id}
    ], 201);
}

    /** Eliminazione */
    public function destroy(Allegato $allegato)
    {
        $disk = $allegato->disk ?: config('filesystems.default', 'dropbox');

        if ($allegato->path && Storage::disk($disk)->exists($allegato->path)) {
            Storage::disk($disk)->delete($allegato->path);
        }
        $allegato->delete();


    // Se è una richiesta AJAX, non forzare redirect Inertia
    if (request()->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->noContent(); // 204
    }

    // fallback per richieste normali
    return back()->with('success', 'Allegato eliminato');


    }
}

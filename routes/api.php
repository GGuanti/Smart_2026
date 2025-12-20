<?php
use App\Http\Controllers\Api\ClientiSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/clienti/search', [ClientiSearchController::class,'__invoke'])->name('api.clienti.search');




Route::post('/upload-plain', function (Request $request) {
    $request->validate([
        'file'     => ['required','file','max:20480'],
        'filename' => ['nullable','string'],   // es. "contratto_123" o "C:\1.pdf"
        'subdir'   => ['nullable','string'],   // es. "pippo/pluto"
        // altri campi liberi: codCliente, idProg, ecc.
    ]);

    $diskName = env('UPLOAD_DISK', 'public');   // public | dropbox
    $disk     = Storage::disk($diskName);

    $file = $request->file('file');
    $ext  = strtolower($file->getClientOriginalExtension());

    // base name dal filename (ignora eventuale path locale tipo C:\..)
    $rawName = $request->input('filename') ?: $file->getClientOriginalName();
    $base    = pathinfo($rawName, PATHINFO_FILENAME);
    $safe    = Str::slug($base) ?: 'file';
    $name    = $safe.'.'.$ext;

    // subdir dinamica (normalizza / e rimuovi slash iniziale)
    $subdir  = $request->string('subdir')->toString() ?: '';
    $subdir  = ltrim(str_replace('\\','/',$subdir), '/');      // "pippo/pluto" o ""

    // cartella finale (se non passi subdir, usa "allegati")
    $dir     = $subdir !== '' ? $subdir : 'allegati';

    // crea directory se manca
    if (method_exists($disk, 'directoryExists') && !$disk->directoryExists($dir)) {
        $disk->createDirectory($dir);
    }

    $fullPath = ($dir ? $dir.'/' : '').$name;

    // elimina se esiste
    if ($disk->exists($fullPath)) {
        $disk->delete($fullPath);
    }

    // salva
    $path = $disk->putFileAs($dir, $file, $name);

    // opzionale: leggi altri metadati che hai inviato
    $codCliente = $request->input('codCliente');
    $idProg     = $request->input('idProg');

    return response()->json([
        'success'    => true,
        'disk'       => $diskName,
        'path'       => $path,         // es. "pippo/pluto/contratto_123.pdf"
        'filename'   => $name,
        'subdir'     => $dir,
        'codCliente' => $codCliente,
        'idProg'     => $idProg,
    ], 200, ['Content-Type'=>'application/json']);
});

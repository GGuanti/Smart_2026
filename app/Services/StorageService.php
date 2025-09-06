<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageService
{
    protected string $defaultDisk;

    public function __construct(?string $defaultDisk = null)
    {
        $this->defaultDisk = $defaultDisk ?? config('filesystems.default', 'local');
    }

    /** Istanza del disco */
    public function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($disk ?? $this->defaultDisk);
    }

    /** Crea (se serve) la cartella sul disco */
    public function ensureDirectory(string $directory, ?string $disk = null): void
    {
        $directory = trim($directory, '/');
        if ($directory === '') {
            return;
        }
        // Su Dropbox è “lazy”, ma è innocuo
        $this->disk($disk)->makeDirectory($directory);
    }

    /**
     * Carica un UploadedFile preservando il nome (slug) ed evitando overwrite con suffissi (1), (2)...
     * Ritorna il path finale salvato (es. "allegati/123/fattura(1).pdf").
     */
    public function putUploadedFilePreservingName(
        UploadedFile $file,
        string $directory,
        ?string $disk = null
    ): string {
        $directory = trim($directory, '/');
        $this->ensureDirectory($directory, $disk);

        $original = $file->getClientOriginalName();
        $base     = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
        $ext      = strtolower($file->getClientOriginalExtension());
        $target   = $directory !== '' ? "{$directory}/{$base}.{$ext}" : "{$base}.{$ext}";

        $finalPath = $this->uniqueName($target, $disk);
        // storeAs richiede solo il nome del file (basename)
        $saved = $file->storeAs($directory, basename($finalPath), $disk ?? $this->defaultDisk);

        return $saved; // es. "allegati/123/fattura(1).pdf"
    }

    /**
     * Carica un UploadedFile con timestamp nel nome (evita collisioni senza check).
     * Esempio: "fattura-20250906-154210.pdf".
     */
    public function putUploadedFileWithTimestamp(
        UploadedFile $file,
        string $directory,
        ?string $disk = null,
        string $format = 'YmdHis'
    ): string {
        $directory = trim($directory, '/');
        $this->ensureDirectory($directory, $disk);

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = "{$base}-" . now()->format($format) . ".{$ext}";

        return $file->storeAs($directory, $name, $disk ?? $this->defaultDisk);
    }

    /**
     * Scrive contenuti testuali/binari evitando overwrite (aggiunge suffissi).
     * Ritorna il path finale usato.
     */
    public function putContentsPreservingName(
        string $path,
        string $contents,
        ?string $disk = null
    ): string {
        $path = $this->normalizePath($path);
        $final = $this->uniqueName($path, $disk);
        Storage::disk($disk ?? $this->defaultDisk)->put($final, $contents);
        return $final;
    }

    /** Esistenza file */
    public function exists(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk ?? $this->defaultDisk)->exists($this->normalizePath($path));
    }

    /** Elimina file */
    public function delete(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk ?? $this->defaultDisk)->delete($this->normalizePath($path));
    }

    /** Elimina directory (ricorsivo) */
    public function deleteDirectory(string $directory, ?string $disk = null): bool
    {
        $directory = trim($directory, '/');
        return Storage::disk($disk ?? $this->defaultDisk)->deleteDirectory($directory);
    }

    /** Dimensione file in byte (o null) */
    public function size(string $path, ?string $disk = null): ?int
    {
        $path = $this->normalizePath($path);
        if (!Storage::disk($disk ?? $this->defaultDisk)->exists($path)) {
            return null;
        }
        return Storage::disk($disk ?? $this->defaultDisk)->size($path);
    }

    /** Mime type (o application/octet-stream) */
    public function mimeType(string $path, ?string $disk = null): ?string
    {
        $path = $this->normalizePath($path);
        if (!Storage::disk($disk ?? $this->defaultDisk)->exists($path)) {
            return null;
        }
        return Storage::disk($disk ?? $this->defaultDisk)->mimeType($path) ?: 'application/octet-stream';
    }

    /**
     * Stream di un file come risposta HTTP (inline=preview, false=download).
     */
    public function streamResponse(
        string $path,
        ?string $downloadName = null,
        bool $inline = true,
        ?string $disk = null
    ): StreamedResponse {
        $path = $this->normalizePath($path);
        $fs   = Storage::disk($disk ?? $this->defaultDisk);

        if (!$fs->exists($path)) {
            abort(404);
        }

        $mime = $fs->mimeType($path) ?: 'application/octet-stream';
        $name = $downloadName ?: basename($path);
        $disposition = $inline ? 'inline' : 'attachment';

        return Response::stream(function () use ($fs, $path) {
            $stream = $fs->readStream($path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => "{$disposition}; filename=\"{$name}\"",
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Genera un path univoco aggiungendo suffissi (1), (2), ... se già esiste.
     * Ritorna il path disponibile.
     */
    public function uniqueName(string $path, ?string $disk = null): string
    {
        $fs   = Storage::disk($disk ?? $this->defaultDisk);
        $path = $this->normalizePath($path);

        if (!$fs->exists($path)) {
            return $path;
        }

        $dir  = trim(dirname($path), '.');
        $dir  = $dir === '/' ? '' : trim($dir, '/');
        $file = basename($path);

        $name = pathinfo($file, PATHINFO_FILENAME);
        $ext  = pathinfo($file, PATHINFO_EXTENSION);

        $i = 1;
        do {
            $candidate = $dir !== ''
                ? "{$dir}/{$name}({$i})" . ($ext ? ".{$ext}" : '')
                : "{$name}({$i})" . ($ext ? ".{$ext}" : '');
            $i++;
        } while ($fs->exists($candidate));

        return $candidate;
    }

    /** Normalizza path: toglie doppi slash e leading/trailing slash */
    protected function normalizePath(string $path): string
    {
        $clean = preg_replace('#/+#', '/', $path);
        return trim($clean ?? $path, '/');
    }
}

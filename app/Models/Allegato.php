<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Allegato extends Model
{
    protected $table = 'allegati';                         // forza il nome tabella
    protected $fillable = ['id_prog','nome','path','mime','size'];

    // Aggiunge automaticamente "url" nel JSON/array
    protected $appends = ['url'];

    // (opzionale) nascondi "path" nell'output JSON
    protected $hidden  = ['path'];

    public function getUrlAttribute(): string
    {
        return $this->path
            ? Storage::disk('public')->url($this->path)
            : '';
    }
}

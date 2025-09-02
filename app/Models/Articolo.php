<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articolo extends Model
{
    protected $table = 'articoli'; // <- AGGIUNGI QUESTA RIGA
    protected $fillable = ['nome', 'descrizione', 'prezzo'];
}

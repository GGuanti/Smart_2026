<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contratto extends Model
{
    protected $table = 'contratti'; // 👈 risolve il problema

    protected $fillable = [
        'IdContratto',
        'NomeCognUser',
        'TipoContr',
        'DataContratto',
        'DataInizio',
        'DataFineContratto',
        'Stato',
        'StatoContratto',
        'CodFiscale',
        'CodCliente',
    ];
    public $timestamps = false;
}

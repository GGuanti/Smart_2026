<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinituraTelaio extends Model
{
    protected $table = 'finitura_telaio';
    protected $primaryKey = 'IdFinTelaio';
    public $timestamps = false;

    protected $casts = [
        'IdFinTelaio' => 'integer',
        'Campo1'      => 'integer',
        'Campo2'      => 'integer',
    ];

    protected $fillable = [
        'Tipologia',
        'Colore',
        'FiltroTipoTel',
        'Campo1',
        'Campo2',
        'Cod1',
        'Cod2',
        'Descr',
        'CodDist',
        'ColKitRt',
    ];
}

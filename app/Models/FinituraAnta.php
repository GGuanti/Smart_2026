<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinituraAnta extends Model
{
    protected $table = 'finitura_anta';
    protected $primaryKey = 'IdFinAnta';
    public $timestamps = false;

    protected $casts = [
        'IdFinAnta' => 'integer',
        'MaggAnta'  => 'integer',
        'Espr1'     => 'boolean',
    ];

    protected $fillable = [
        'Tipologia',
        'Colore',
        'MaggAnta',
        'Cod1',
        'Cod2',
        'Descr',
        'CodDist',
        'Espr1',
    ];
}

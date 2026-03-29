<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarioIsomax extends Model
{
    protected $table = 'calendario_isomax';

protected $fillable = [
    'title',
    'description',
    'DataInizio',
    'DataFine',
    'DataConferma',
    'DataConsegna',
    'status',
    'StatoMagazzino',
    'Nordine',
    'Riferimento',
    'Colore',
    'Annotazioni',
    'Pezzi',
    'user_id',
];

    protected $casts = [
        'DataInizio' => 'datetime',
        'DataFine' => 'datetime',
    ];

    // ✅ RELAZIONE CORRETTA
    public function items()
    {
        return $this->hasMany(CalendarioProdotti::class, 'calendario_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{

    protected $fillable = [
        'title',
        'description',
        'DataInizio',
        'DataFine',
        'status',
        'user_id',
        'Nordine',
        'DataConferma',
        'DataConsegna',
        'Colore',
        'Riferimento',
        'Pezzi',
        'T',
        'TZ',
        'TL',
        'A',
        'C',
        'L',
        'Annotazioni',
        'StatoMagazzino',

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

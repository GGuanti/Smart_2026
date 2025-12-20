<?php

namespace App\Models;

use App\Models\AppointmentItem;
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
        'Annotazioni',
        'Prodotto',
        'StatoMagazzino',

    ];
    protected $casts = [
        'Nordine' => 'integer',
        'Pezzi' => 'integer',
        // Se nel DB sono DATE:
        'DataInizio'   => 'date:Y-m-d',
        'DataFine'     => 'date:Y-m-d',
        'DataConferma' => 'date:Y-m-d',
        'DataConsegna' => 'date:Y-m-d',
        'Prodotto' => 'array',
    ];


    public function items()
    {
        return $this->hasMany(AppointmentItem::class, 'Nordine', 'Nordine');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

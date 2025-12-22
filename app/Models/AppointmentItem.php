<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentItem extends Model
{
    use HasFactory;

    protected $table = 'appointment_items';

    protected $fillable = [
        'Nordine',
        'Prodotto',
        'Colore',
        'Pezzi',
        'Descrizione',
        'Taglio',
        'Assemblaggio',
        'Comandi',
        'TaglioZoccolo',
        'TaglioLamelle',
        'MontaggioLamelle',
        'Ferramenta',
        'Vetratura',
        'Accessori',
    'Coprifili',
    'Fermavetri',
    'OrdineVetri',
    ];

    protected $casts = [
        'Nordine' => 'integer',
        'Pezzi' => 'integer',
        'Taglio' => 'boolean',
        'Assemblaggio' => 'boolean',
        'Comandi' => 'boolean',
        'TaglioZoccolo' => 'boolean',
        'TaglioLamelle' => 'boolean',
        'MontaggioLamelle' => 'boolean',
        'Ferramenta' => 'boolean',
        'Vetratura' => 'boolean',
        'Accessori' => 'boolean',
        'Coprifili' => 'boolean',
        'Fermavetri' => 'boolean',
        'OrdineVetri' => 'boolean',

    ];

    /**
     * Relazione inversa su Nordine (non su id)
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'Nordine', 'Nordine');
    }
}

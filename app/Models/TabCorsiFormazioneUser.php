<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabCorsiFormazioneUser extends Model
{
    protected $table = 'TabCorsiFormazioneUser';
    protected $primaryKey = 'IdTabCorso'; // Assicurati che sia la chiave primaria corretta

    public $timestamps = false; // Se non hai created_at / updated_at

    protected $fillable = [
        'IdCorso',
        'CodCliente',
        'DataAttestato',
        'Note',
        'Stato',
        'UtenteMod',
        'DataModifica',
    ];

    // Relazione con TabCorsiFormazione
    public function corso()
    {
        return $this->belongsTo(TabCorsiFormazione::class, 'IdCorso', 'IdCorso');
    }
}

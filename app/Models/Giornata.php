<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Giornata extends Model
{
    protected $table = 'giornate';
    protected $primaryKey = 'IdGiornate';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'IDContratto',
        'Denominazione_luogo',
        'Data',
        'Indirizzo',
        'CAP',
        'Comune',
        'Comune_straniero',
        'Provincia',
        'Sigla',
        'Stato',
        'Retribuzione',
        'DIARIA',
        'CodiceAttivita',
        'Utente',
        'CodCliente',
        'CodUser',
        'UtenteMod',
        'DataModifica',
        'Mansione',
        'TipoContrSimulatore',
    ];

    protected $casts = [
        'Data'          => 'datetime:Y-m-d H:i:s',
        'Retribuzione'  => 'decimal:2',
        'DIARIA'        => 'boolean',
        'DataModifica'  => 'datetime:Y-m-d H:i:s',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    /* Relazioni opzionali
    public function contratto()
    {
        return $this->belongsTo(Contratto::class, 'IDContratto', 'IdContratto');
    }

    public function attivita()
    {
        return $this->belongsTo(Attivita::class, 'CodiceAttivita', 'IdAttivita');
    }
    */
}

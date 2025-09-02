<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabProgetto extends Model
{
    use HasFactory;

    protected $table = 'eprogetti'; // Nome tabella nel DB

    protected $primaryKey = 'IdProg'; // Chiave primaria, se diversa, es: 'IDProgetto'

    public $timestamps = true; // Se non usi created_at e updated_at metti false

    protected $fillable = [
        'DataContratto',
                'Descrizioneprogetto',
                'Accordi',
                'ImportoNettoConcordato',
                'IdProgetto',
                'IdAttivita',
                'IdProg',
                'CodCliente',
                'RagioneSocialeCommittenti',
                'TipologiaSimulatore',
        // aggiungi qui altri campi presenti nella tabella
    ];

    // 🔁 Relazione con cliente/anagrafica
    public function cliente()
    {
        return $this->belongsTo(Anagrafica::class, 'CodCliente', 'CodCliente');
    }
}

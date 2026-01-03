<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class TabOrdine extends Model
{



    use HasFactory;

    // ✅ Nome tabella MySQL (metti quello che hai in migration)
    protected $table = 'tab_ordine';

    // ✅ PK come in Access/SQL Server: "ID"
    protected $primaryKey = 'ID';

    // ✅ Identity/autoincrement
    public $incrementing = true;
    protected $keyType = 'int';

    // ✅ Se nella tabella NON hai created_at / updated_at
    public $timestamps = false;
    // Se invece li hai, metti true:
    // public $timestamps = true;

    // Cast utili
    protected $casts = [
        'CkRilievo'     => 'boolean',
        'CkMontaggio'   => 'boolean',
        'CkImballo'     => 'boolean',
        'CkRagFatt'     => 'boolean',
        'OrdineSel'     => 'boolean',
        'Ultimo'        => 'boolean',
        'Produci'       => 'boolean',
        'Prodotto'      => 'boolean',

        'DataOrdine'    => 'datetime',
        'DataCons'      => 'datetime',
        'DataFattura'   => 'datetime',
        'DataProduzione' => 'datetime',
        'DataProdotto'  => 'datetime',
        'DataConferma'  => 'datetime',

        'Sconto1'       => 'float',
        'Sconto2'       => 'float',
        'Listino'       => 'float',
        'ImportoMan'    => 'float',
        'CstMontaggio'  => 'float',
        'CstTrasporto'  => 'float',
        'KmMontaggio'   => 'integer',
        'IdIva'         => 'integer',
        'IdTipoDoc'     => 'integer',
        'Nordine'       => 'integer',
    ];

    // ✅ Campi compilabili (mass assignment)
    protected $fillable = [
        'Nordine',
        'CognomeNome',
        'Telefono',
        'Cellulare',
        'Indirizzo',
        'IdCitta',
        'Provincia',
        'CAP',
        'Annotazioni',
        'CodFiscale',
        'PIva',
        'user_id',
        'CognomeNomeFatt',
        'TelefonoFatt',
        'CellulareFatt',
        'IndirizzoFatt',
        'IdCittaFatt',
        'ProvinciaFatt',
        'CAPFatt',
        'AnnotazioniFatt',
        'CodFiscaleFatt',
        'PIvaFatt',

        'Sconto1',
        'Sconto2',
        'Listino',

        'CkRilievo',
        'CkMontaggio',
        'SelTrasporto',
        'IdZonaClimatica',
        'Fascia',
        'UgFascia',
        'IdAgente',

        'DataOrdine',
        'LivMare',
        'ImportoMan',
        'Progettista',
        'DataCons',
        'TipoDoc',
        'CkImballo',
        'IdIva',
        'KmMontaggio',
        'CstMontaggio',
        'CstTrasporto',
        'TxtConsegna',
        'TxtModPagamento',

        'CodCliFor',
        'DesCliFor',
        'Agente',
        'Email',

        'OrdVetro',
        'OrdBugna',
        'RifOrdVetro',
        'RifOrdBugna',
        'Provv',

        'IdTipoDoc',
        'DataFattura',
        'CkRagFatt',

        'Variabili',

        'OrdineSel',
        'Ultimo',
        'Produci',
        'Prodotto',

        'Utente',
        'DataProduzione',
        'DataProdotto',
        'DataConferma',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    /*
    |--------------------------------------------------------------------------
    | Relazioni
    |--------------------------------------------------------------------------
    | L'ordine è legato agli elementi (TabElementiOrdine) tramite Nordine
    */
    public function elementi()
    {
        return $this->hasMany(TabElementiOrdine::class, 'Nordine', 'Nordine');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: sconto totale composto
    |--------------------------------------------------------------------------
    */
    public function getScontoTotaleAttribute(): float
    {
        $s1 = max(0, min(100, (float)($this->Sconto1 ?? 0)));
        $s2 = max(0, min(100, (float)($this->Sconto2 ?? 0)));

        $a = $s1 / 100;
        $b = $s2 / 100;

        $tot = 1 - (1 - $a) * (1 - $b); // composto
        return round($tot * 100, 2);
    }
    public function righe()
{
    // FK nella tabella righe = Nordine
    // chiave locale nell'ordine = Nordine
    return $this->hasMany(\App\Models\TabElementiOrdine::class, 'Nordine', 'Nordine');
}
}

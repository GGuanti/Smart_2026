<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordine extends Model
{
    protected $table = 'tab_ordine';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Nordine',

        // Anagrafica
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
        'IdTrasporto',
        'IdIva',
        // Fatturazione
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

        // Economici / flag
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

        // Stati
        'OrdineSel',
        'Ultimo',
        'Produci',
        'Prodotto',

        'Utente',
        'DataProduzione',
        'DataProdotto',
        'DataConferma',
    ];

    protected $casts = [
        // boolean
        'CkRilievo'    => 'boolean',
        'CkMontaggio'  => 'boolean',
        'CkImballo'    => 'boolean',
        'CkRagFatt'    => 'boolean',
        'OrdineSel'    => 'boolean',
        'Ultimo'       => 'boolean',
        'Produci'      => 'boolean',
        'Prodotto'     => 'boolean',

        // datetime
        'DataOrdine'      => 'datetime',
        'DataCons'        => 'datetime',
        'DataFattura'     => 'datetime',
        'DataProduzione'  => 'datetime',
        'DataProdotto'    => 'datetime',
        'DataConferma'    => 'datetime',

        // numerici
        'Sconto1'      => 'float',
        'Sconto2'      => 'float',
        'Listino'      => 'float',
        'ImportoMan'   => 'float',
        'CstMontaggio' => 'float',
        'CstTrasporto' => 'float',
        'KmMontaggio'  => 'integer',
        'IdIva'        => 'integer',
        'IdTrasporto' => 'integer',
        'IdTipoDoc'    => 'integer',
    ];

    /**
     * Default automatico per Variabili (se nullo)
     */
    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->Variabili)) {
                $m->Variabili =
                    'TabPage=0;CmbDimL=880;CmbDimA=2140;CmbDimSp=110;TxtQta=1;' .
                    'TxtPrezzoMan=0;TxtPrezListino=0;CmbModello=14;CmbAnta=179;' .
                    'CmbTelaio=175;CmbTipoTelaio=654;CmbImbotte=40;CmbSoluzioni=27;' .
                    'CmbManiglia=25;CmbApertura=2;CmbVetro=1;CmbFerramenta=38;' .
                    'CmbSerratura=11;CmbTaglioObliquo=No;TxNoteListino=;';
            }
        });
    }
    public function righe()
    {
        return $this->hasMany(OrdineRiga::class, 'ordine_id');
    }

    /**
     * Relazione: Ordine → Elementi Ordine (righe)
     */
    public function elementi()
    {
        return $this->hasMany(
            \App\Models\TabElementoOrdine::class,
            'Nordine',
            'Nordine'
        );
    }
}

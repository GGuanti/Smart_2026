<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listino extends Model
{
    protected $table = 'listini';

    protected $primaryKey = 'id_listino';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        // ordinamento
        'ordinamento',
        // descrittivi
        'campo3',
        'nome_modello',
        'finiture_anta',
        'finiture_telaio',
        'telaio',
        'soluzioni',
        'vetro',

        // tipologie / sistemi
        'bt',
        'bt2s',
        'bt2a',
        'si',
        'sis',
        'si2m',
        'si2s',
        'se',
        'ses',
        'se2m',
        'se2s',
        'libs',
        'liba',
        'rt',
        'eslidem1',
        'eslides1',
        'eslidem2',
        'eslides2',

        // maniglie / misure
        'maniglie',
        'magg_lrg',
        'magg_lrg90',
        'magg_lrg100',
        'magg_lrg101',
        'magg_alt_minus',
        'magg_tgl_obl',
        'magg_alt_plus',

        // commerciali
        'collezione',
        'magg_detrazioni',
        'magg_svetratura',
        'note',
        'filtro_tit_tel',

        // varianti
        'var_pred1',
        'var_pred2',
        'var_pred3',
        'var_pred4',

        // flags
        'limiti_dim',
        'nascondi',

        // tecnici
        'dis_libro_simm',
        'tipo_battuta',
        'campo1',
        'campo2',
        'telp',
        'verifica_librb',
        'librb',

        // stampa / messaggi
        'stampa_sp',
        'msg_errore',

        // HManiglia
        'hmaniglia_bt',
        'hmaniglia_bt2a',
        'hmaniglia_bt2s',
        'hmaniglia_eslidem1',
        'hmaniglia_eslidem2',
        'hmaniglia_liba',
        'hmaniglia_librb',
        'hmaniglia_libs',
        'hmaniglia_rt',
        'hmaniglia_se',
        'hmaniglia_se2m',
        'hmaniglia_se2s',
        'hmaniglia_ses',
        'hmaniglia_si',
        'hmaniglia_si2m',
        'hmaniglia_si2s',
        'hmaniglia_sis',
        'hmaniglia_telbt',
        'hmaniglia_telp',
        'hmaniglia_telsi',
    ];

    protected $casts = [
        'id_listino'=> 'integer',
        'limiti_dim' => 'boolean',
        'nascondi'   => 'boolean',
    ];
}

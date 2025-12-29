<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabSoluzione extends Model
{
    protected $table = 'tab_soluzioni';
    protected $primaryKey = 'id_tab_soluzioni';

    protected $fillable = [
        'tipologia',
        'soluzione',
        'ass_collistino',
        'immagine',
        'img_soluzioni',
        'nante',
        'filtro_serr',
        'costo_montaggio',
        'des_stampa',
        'fasci_mant',
        'magg_fasc_mant',
        'cod_porta',
    ];
}

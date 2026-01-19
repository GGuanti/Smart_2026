<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListinoValPred extends Model
{
    protected $table = 'listino_valpred';

    protected $fillable = [
        'id_listino',
        'id_tab_soluzioni',
        'id_col_telaio', // ✅
        'valpred',
    ];



    protected $casts = [
        'valpred' => 'array',
    ];
}

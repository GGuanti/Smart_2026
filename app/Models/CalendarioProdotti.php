<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarioProdotti extends Model
{
    protected $table = 'calendario_prodotti';

    protected $fillable = [
        'calendario_id',
        'Prodotto',
        'Pezzi',
    ];
}

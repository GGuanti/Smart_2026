<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabVisiteMediche extends Model
{
    use HasFactory;

    protected $table = 'tab_visite_mediche';
    protected $primaryKey = 'IdVisita';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'UtenteMod',
        'DataModifica',
        'DataVisita',
        'DataScadenza',
        'CodCliente',
    ];
}

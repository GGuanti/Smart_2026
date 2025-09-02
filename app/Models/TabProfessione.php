<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabProfessione extends Model
{
    protected $table = 'tab_professioni';
    public $timestamps = false;

    protected $fillable = [
        'Professione','UniLav','LivelloCCNL','Minima','CodUniLav','Settore'
    ];
}

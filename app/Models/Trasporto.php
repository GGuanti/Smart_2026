<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trasporto extends Model
{
    protected $table = 'tab_trasporto';   // 👈 NOME TABELLA ESATTO
    protected $primaryKey = 'id';          // 👈 PK (se diversa, dimmelo)
    public $timestamps = false;            // se non hai created_at/updated_at
}

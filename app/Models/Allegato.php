<?php

// app/Models/Allegato.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allegato extends Model
{
    protected $table = 'allegati';          // importante: niente "allegatos"
    protected $fillable = ['id_prog','nome','path','mime','size'];
    public $timestamps = true;              // metti false se non hai created_at/updated_at
}

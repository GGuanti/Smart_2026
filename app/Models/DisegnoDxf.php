<?php
// app/Models/DisegnoDxf.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisegnoDxf extends Model
{
    protected $table = 'DisegniDXF';
    protected $primaryKey = 'IdRigaDXF';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'Codice','Descrizione','LRG','ALT','Dxf'
    ];
}

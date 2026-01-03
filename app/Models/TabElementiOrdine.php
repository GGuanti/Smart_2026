<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TabElementiOrdine extends Model
{
    use HasFactory;

    protected $table = 'tab_elementi_ordine';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $casts = [
        'Id'         => 'integer',
        'Nordine'    => 'integer',

        'DimL'       => 'float',
        'DimA'       => 'float',
        'DimSp'      => 'float',
        'Qta'        => 'float',

        'PrezzoCad'  => 'float',
        'PrezzoMan'  => 'float',

        'IdModello'   => 'integer',
        'IdColAnta'   => 'integer',
        'IdColTelaio' => 'integer',
        'IdSoluzione' => 'integer',
        'IdManiglia'  => 'integer',
        'IdApertura'  => 'integer',
        'IdTipTelaio' => 'integer',
        'IdVetro'     => 'integer',
        'IdColFerr'   => 'integer',
        'IdSerratura' => 'integer',
        'IdImbotte'   => 'integer',

        'CkTaglioObl' => 'string',
        'NoteMan'     => 'string',
        'PercFile'    => 'string',
        'TxtCassMet'  => 'string',
    ];

    protected $fillable = [
        'Nordine',

        'DimL','DimA','DimSp','Qta',
        'PrezzoCad','PrezzoMan',

        'NoteMan','PercFile',

        'IdModello','IdColAnta','IdColTelaio','IdSoluzione','IdManiglia','IdApertura',
        'IdTipTelaio','IdVetro','IdColFerr','IdSerratura',
        'CkTaglioObl','IdImbotte','TxtCassMet',
    ];

    public function ordine()
    {
        return $this->belongsTo(TabOrdine::class, 'Nordine', 'Nordine');
    }

    protected $guarded = [];


}

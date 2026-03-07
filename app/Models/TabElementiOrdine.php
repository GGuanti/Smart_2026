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
         'accessori_sel' => 'array',
    ];

    protected $fillable = [
        'Nordine',

        'DimL','DimA','DimSp','Qta',
        'PrezzoCad','PrezzoMan',

        'NoteMan','PercFile',

        'IdModello','IdColAnta','IdColTelaio','IdSoluzione','IdManiglia','IdApertura',
        'IdTipTelaio','IdVetro','IdColFerr','IdSerratura',
        'CkTaglioObl','IdImbotte','TxtCassMet',
         'accessori_sel',
    ];
public function getAccessoriSelAttribute($value)
{
    if ($value === null || $value === '') return [];

    if (is_array($value)) return $value;

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    return [];
}
    public function ordine()
    {
        return $this->belongsTo(TabOrdine::class, 'Nordine', 'Nordine');
    }

  //  protected $guarded = [];


}

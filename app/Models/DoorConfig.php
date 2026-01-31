<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoorConfig extends Model
{
    protected $fillable = [
        'user_id','opening_type','anta_texture_id','telaio_texture_id','params','name'
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function antaTexture(){ return $this->belongsTo(Texture::class, 'anta_texture_id'); }
    public function telaioTexture(){ return $this->belongsTo(Texture::class, 'telaio_texture_id'); }
}

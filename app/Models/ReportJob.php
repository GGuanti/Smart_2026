<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportJob extends Model
{
    use HasFactory;

    protected $table = 'report_jobs';

    protected $fillable = [
        'nomefile',
        'nome_report',
        'filtro',
        'eseguito',
    ];

    protected $casts = [
        'filtro' => 'array',
        'eseguito' => 'boolean',
    ];
}

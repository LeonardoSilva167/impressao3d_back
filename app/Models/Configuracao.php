<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuracao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'configuracoes';

    protected $fillable = [
        'proximo_codigo_base',
        'custo_energia_kwh',
        'custo_desgaste_hora',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'proximo_codigo_base' => 'integer',
        'custo_energia_kwh'   => 'decimal:4',
        'custo_desgaste_hora' => 'decimal:4',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    protected $dates = ['deleted_at'];
}

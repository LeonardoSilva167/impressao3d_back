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
    ];

    protected $casts = [
        'id'                  => 'integer',
        'proximo_codigo_base' => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    protected $dates = ['deleted_at'];
}

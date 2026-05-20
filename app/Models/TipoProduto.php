<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoProduto extends Model
{
    use HasFactory,  SoftDeletes;
    
    protected $table = 'tipo_produtos';

    protected $fillable = [
        'nome', 
        'ativo', 
    ];

    protected $casts = [
        'nome' => 'string',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
    
}

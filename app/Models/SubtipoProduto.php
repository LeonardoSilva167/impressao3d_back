<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubtipoProduto extends Model
{
    use HasFactory,  SoftDeletes;
    
    protected $table = 'subtipo_produtos';

    protected $fillable = [
        'tipo_produtos_id', 
        'nome', 
        'ativo', 
    ];

    protected $casts = [
        'tipo_produtos_id' => 'integer',
        'nome' => 'string',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
   
    public function tipo_produtos()
    {
        return $this->belongsTo(Orgao::class, 'tipo_produtos_id');
    }
}

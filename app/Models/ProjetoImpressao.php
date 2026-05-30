<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetoImpressao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projetos_impressao';

    protected $fillable = [
        'url_projeto',
        'nome_original_projeto',
        'codigo_projeto',
        'descricao_projeto',
    ];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function partes(): HasMany
    {
        return $this->hasMany(ProjetoImpressaoParte::class, 'id_projeto_impressao');
    }
}

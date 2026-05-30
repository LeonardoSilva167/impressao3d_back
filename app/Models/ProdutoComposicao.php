<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoComposicao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produto_composicoes';

    protected $fillable = [
        'id_produto',
        'id_projeto_impressao',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'id_produto'          => 'integer',
        'id_projeto_impressao' => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function produtoBase()
    {
        return $this->belongsTo(ProdutoBase::class, 'id_produto');
    }

    public function projetoImpressao()
    {
        return $this->belongsTo(ProjetoImpressao::class, 'id_projeto_impressao');
    }

    public function cores()
    {
        return $this->hasMany(ProdutoComposicaoCor::class, 'id_composicao');
    }

    public function variacoes()
    {
        return $this->hasMany(ProdutoVariacao::class, 'id_composicao');
    }
}

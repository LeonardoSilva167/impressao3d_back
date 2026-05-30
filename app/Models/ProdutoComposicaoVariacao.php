<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoComposicaoVariacao extends Model
{
    use HasFactory;

    protected $table = 'produto_composicao_variacoes';

    protected $fillable = [
        'id_produto_composicao',
        'id_produto_variacao',
        'custo_total_filamentos',
        'tempo_total_impressao',
    ];

    protected $casts = [
        'id'                     => 'integer',
        'id_produto_composicao'  => 'integer',
        'id_produto_variacao'    => 'integer',
        'custo_total_filamentos' => 'decimal:4',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function composicao()
    {
        return $this->belongsTo(ProdutoComposicao::class, 'id_produto_composicao');
    }

    public function produtoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_produto_variacao');
    }

    public function itens()
    {
        return $this->hasMany(ProdutoComposicaoItem::class, 'id_produto_composicao_variacao');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoComposicaoItem extends Model
{
    use HasFactory;

    protected $table = 'produto_composicao_itens';

    protected $fillable = [
        'id_produto_composicao_variacao',
        'id_item_projeto',
        'id_filamento',
        'peso_total',
        'tempo_impressao',
        'preco_medio_grama',
        'custo_item',
    ];

    protected $casts = [
        'id'                             => 'integer',
        'id_produto_composicao_variacao' => 'integer',
        'id_item_projeto'                => 'integer',
        'id_filamento'                   => 'integer',
        'peso_total'                     => 'decimal:2',
        'preco_medio_grama'              => 'decimal:4',
        'custo_item'                     => 'decimal:4',
        'created_at'                     => 'datetime',
        'updated_at'                     => 'datetime',
    ];

    public function variacao()
    {
        return $this->belongsTo(ProdutoComposicaoVariacao::class, 'id_produto_composicao_variacao');
    }

    public function itemProjeto()
    {
        return $this->belongsTo(ProjetoImpressaoParteItem::class, 'id_item_projeto');
    }

    public function filamento()
    {
        return $this->belongsTo(Filamento::class, 'id_filamento');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoVariacaoFilamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produto_variacao_filamentos';

    protected $fillable = [
        'id_variacao',
        'id_filamento',
        'preco_medio_grama',
        'peso_item',
        'custo_filamento',
        'custo_energia',
        'custo_desgaste',
        'custo_total',
    ];

    protected $casts = [
        'id'                => 'integer',
        'id_variacao'       => 'integer',
        'id_filamento'      => 'integer',
        'preco_medio_grama' => 'decimal:4',
        'peso_item'         => 'decimal:2',
        'custo_filamento'   => 'decimal:4',
        'custo_energia'     => 'decimal:4',
        'custo_desgaste'    => 'decimal:4',
        'custo_total'       => 'decimal:4',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_variacao');
    }

    public function filamento()
    {
        return $this->belongsTo(Filamento::class, 'id_filamento');
    }
}

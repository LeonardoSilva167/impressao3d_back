<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentacaoEstoque extends Model
{
    use HasFactory;

    public const TIPO_ENTRADA_COMPRA  = 'ENTRADA_COMPRA';
    public const TIPO_SAIDA_VENDA     = 'SAIDA_VENDA';
    public const TIPO_CONSUMO_TESTE     = 'CONSUMO_TESTE';
    public const TIPO_ERRO_IMPRESSAO  = 'ERRO_IMPRESSAO';
    public const TIPO_DESCARTE        = 'DESCARTE';
    public const TIPO_AJUSTE               = 'AJUSTE';
    public const TIPO_FINALIZACAO_CARRETEL        = 'FINALIZACAO_CARRETEL';
    public const TIPO_ESTORNO_FINALIZACAO_CARRETEL  = 'ESTORNO_FINALIZACAO_CARRETEL';
    public const TIPO_CANCELAMENTO_COMPRA           = 'CANCELAMENTO_COMPRA';

    public const TIPOS_SAIDA = [
        self::TIPO_SAIDA_VENDA,
        self::TIPO_CONSUMO_TESTE,
        self::TIPO_ERRO_IMPRESSAO,
        self::TIPO_DESCARTE,
        self::TIPO_AJUSTE,
        self::TIPO_FINALIZACAO_CARRETEL,
    ];

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'id_item',
        'id_compra_item',
        'tipo_movimentacao',
        'qtd',
        'gramatura',
        'saldo_anterior',
        'saldo_posterior',
        'id_carreteis_finalizados',
        'observacao',
        'data_movimentacao',
    ];

    protected $casts = [
        'id'                       => 'integer',
        'id_item'                  => 'integer',
        'id_compra_item'           => 'integer',
        'qtd'                      => 'decimal:4',
        'gramatura'                => 'integer',
        'saldo_anterior'           => 'decimal:4',
        'saldo_posterior'          => 'decimal:4',
        'id_carreteis_finalizados' => 'integer',
        'data_movimentacao'        => 'datetime',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }

    public function compraItem()
    {
        return $this->belongsTo(CompraItem::class, 'id_compra_item');
    }

    public function carreteisFinalizado()
    {
        return $this->belongsTo(CarreteisFinalizado::class, 'id_carreteis_finalizados');
    }
}

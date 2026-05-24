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
    public const TIPO_AJUSTE          = 'AJUSTE';

    public const TIPOS_SAIDA = [
        self::TIPO_SAIDA_VENDA,
        self::TIPO_CONSUMO_TESTE,
        self::TIPO_ERRO_IMPRESSAO,
        self::TIPO_DESCARTE,
        self::TIPO_AJUSTE,
    ];

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'id_item',
        'tipo_movimentacao',
        'qtd',
        'observacao',
        'data_movimentacao',
    ];

    protected $casts = [
        'id'                => 'integer',
        'id_item'           => 'integer',
        'qtd'               => 'decimal:4',
        'data_movimentacao' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}

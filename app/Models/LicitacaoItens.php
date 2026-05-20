<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicitacaoItens extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'licitacao_itens';

    protected $fillable = [
        'licitacao_id',
        'status_item_id',
        'status_classificacao_id',
        'num_item',
        'descricao', 
        'qtd',
        'preco_teto',
        'total_teto',
        'valor_lance',
        'total_lance',
        'valor_negociado',
        'total_negociado'
    ];

    protected $casts = [
        'licitacao_id' => 'integer',
        'status_item_id' => 'integer',
        'status_classificacao_id' => 'integer',
        'num_item' => 'integer',
        'descricao' => 'datestringtime',
        'qtd' => 'decimal',
        'preco_teto' => 'decimal',
        'total_teto' => 'decimal',
        'valor_lance' => 'decimal',
        'total_lance' => 'decimal',
        'valor_negociado' => 'decimal',
        'total_negociado' => 'decimal',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function licitacao()
    {
        return $this->belongsTo(Licitacoes::class);
    }
    public function status_item()
    {
        return $this->belongsTo(StatusItem::class);
    }
    public function status_classificacao()
    {
        return $this->belongsTo(StatusClassificacao::class);
    }
}
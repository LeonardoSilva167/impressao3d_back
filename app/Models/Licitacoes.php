<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Licitacoes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'licitacoes';


    protected $fillable = [
        'unidade_compradoras_id',
        'status_licitacoes_id',
        'status_compra_id', 
        'status_cotacao_id',
        'modalidade_id',
        'num_compra',
        'exercicio',
        'data_limite_proposta',
        'link_pcnp'
    ];

    protected $casts = [
        'num_compra' => 'integer',
        'exercicio' => 'integer',
        'data_limite_proposta' => 'datetime',
        'link_pcnp' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function unidade_compradoras()
    {
        return $this->belongsTo(UnidadeCompradora::class);
    }
    public function status_licitacoes()
    {
        return $this->belongsTo(StatusLicitacao::class);
    }
    public function status_compra()
    {
        return $this->belongsTo(StatusCompra::class);
    }
    public function status_cotacao()
    {
        return $this->belongsTo(StatusCotacao::class);
    }
    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }
    
    
}

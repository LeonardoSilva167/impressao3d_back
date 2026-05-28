<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetoImpressaoParte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projetos_impressao_partes';

    protected $fillable = [
        'id_projeto_impressao',
        'nome_parte',
        'altura_camada',
        'temperatura_bico',
        'temperatura_mesa',
        'tempo_impressao',
        'peso_parte',
        'peso_suporte',
        'peso_corado',
        'peso_torre',
        'usa_suporte',
        'angulo_suporte',
        'tipo_suporte',
        'distancia_z_inferior',
        'quantidade_voltas_suporte',
        'usa_brim',
        'usa_engomagem',
        'velocidade_engomagem',
        'fluxo_engomagem',
        'loops_parede',
    ];

    protected $casts = [
        'id'                         => 'integer',
        'id_projeto_impressao'       => 'integer',
        'altura_camada'              => 'decimal:2',
        'temperatura_bico'           => 'integer',
        'temperatura_mesa'           => 'integer',
        'peso_parte'                 => 'decimal:2',
        'peso_suporte'               => 'decimal:2',
        'peso_corado'                => 'decimal:2',
        'peso_torre'                 => 'decimal:2',
        'usa_suporte'                => 'boolean',
        'angulo_suporte'             => 'decimal:2',
        'distancia_z_inferior'       => 'decimal:2',
        'quantidade_voltas_suporte'  => 'integer',
        'usa_brim'                   => 'boolean',
        'usa_engomagem'              => 'boolean',
        'velocidade_engomagem'       => 'decimal:2',
        'fluxo_engomagem'            => 'decimal:2',
        'loops_parede'               => 'integer',
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
        'deleted_at'                 => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(ProjetoImpressao::class, 'id_projeto_impressao');
    }
}

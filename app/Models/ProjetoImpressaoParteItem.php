<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetoImpressaoParteItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projetos_impressao_parte_itens';

    protected $fillable = [
        'id_projeto_impressao_parte',
        'nome_item',
        'id_cor',
        'altura_camada',
        'temperatura_bico',
        'temperatura_mesa',
        'loops_parede',
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
    ];

    protected $casts = [
        'id'                         => 'integer',
        'id_projeto_impressao_parte' => 'integer',
        'id_cor'                     => 'integer',
        'altura_camada'              => 'decimal:2',
        'temperatura_bico'           => 'integer',
        'temperatura_mesa'           => 'integer',
        'loops_parede'               => 'integer',
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
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
        'deleted_at'                 => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function parte(): BelongsTo
    {
        return $this->belongsTo(ProjetoImpressaoParte::class, 'id_projeto_impressao_parte');
    }

    public function cor(): BelongsTo
    {
        return $this->belongsTo(Cor::class, 'id_cor');
    }
}

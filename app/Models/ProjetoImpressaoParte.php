<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetoImpressaoParte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projetos_impressao_partes';

    protected $fillable = [
        'id_projeto_impressao',
        'nome_parte',
        'custo_filamento',
        'custo_energia',
        'custo_desgaste',
        'custo_total',
    ];

    protected $casts = [
        'id'                   => 'integer',
        'id_projeto_impressao' => 'integer',
        'custo_filamento'      => 'decimal:4',
        'custo_energia'        => 'decimal:4',
        'custo_desgaste'       => 'decimal:4',
        'custo_total'          => 'decimal:4',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'deleted_at'           => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(ProjetoImpressao::class, 'id_projeto_impressao');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ProjetoImpressaoParteItem::class, 'id_projeto_impressao_parte');
    }
}

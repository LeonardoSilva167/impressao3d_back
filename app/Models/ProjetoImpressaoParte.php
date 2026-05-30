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
    ];

    protected $casts = [
        'id'                   => 'integer',
        'id_projeto_impressao' => 'integer',
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

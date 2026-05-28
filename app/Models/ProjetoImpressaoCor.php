<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetoImpressaoCor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projetos_impressao_cores';

    protected $fillable = [
        'id_projeto_impressao',
        'id_cor',
        'peso_gramas',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'id_projeto_impressao'=> 'integer',
        'id_cor'              => 'integer',
        'peso_gramas'         => 'decimal:2',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(ProjetoImpressao::class, 'id_projeto_impressao');
    }

    public function cor(): BelongsTo
    {
        return $this->belongsTo(Cor::class, 'id_cor');
    }
}

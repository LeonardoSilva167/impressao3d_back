<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoVariacao extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ATIVA = 'ATIVA';
    public const STATUS_INATIVADA = 'INATIVADA';

    protected $table = 'produto_variacoes';

    protected $fillable = [
        'id_produto_base',
        'id_cor_primaria',
        'id_cor_secundaria',
        'id_cor_terciaria',
        'sku',
        'status',
    ];

    protected $casts = [
        'id'                => 'integer',
        'id_produto_base'   => 'integer',
        'id_cor_primaria'   => 'integer',
        'id_cor_secundaria' => 'integer',
        'id_cor_terciaria'  => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function produtoBase()
    {
        return $this->belongsTo(ProdutoBase::class, 'id_produto_base');
    }

    public function corPrimaria()
    {
        return $this->belongsTo(Cor::class, 'id_cor_primaria');
    }

    public function corSecundaria()
    {
        return $this->belongsTo(Cor::class, 'id_cor_secundaria');
    }

    public function corTerciaria()
    {
        return $this->belongsTo(Cor::class, 'id_cor_terciaria');
    }
}

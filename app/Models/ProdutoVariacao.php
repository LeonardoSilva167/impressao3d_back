<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoVariacao extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPO_PRIMARIA = 'PRIMARIA';
    public const TIPO_SECUNDARIA = 'SECUNDARIA';
    public const TIPO_TERCIARIA = 'TERCIARIA';

    public const TIPOS_COR = [
        self::TIPO_PRIMARIA,
        self::TIPO_SECUNDARIA,
        self::TIPO_TERCIARIA,
    ];

    protected $table = 'produto_variacoes';

    protected $fillable = [
        'id_composicao',
        'id_parte',
        'id_item_projeto',
        'tipo_cor',
        'id_cor',
        'id_composicao_cor',
    ];

    protected $casts = [
        'id'                 => 'integer',
        'id_composicao'      => 'integer',
        'id_parte'           => 'integer',
        'id_item_projeto'    => 'integer',
        'id_cor'             => 'integer',
        'id_composicao_cor'  => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function composicao()
    {
        return $this->belongsTo(ProdutoComposicao::class, 'id_composicao');
    }

    public function parte()
    {
        return $this->belongsTo(ProjetoImpressaoParte::class, 'id_parte');
    }

    public function itemProjeto()
    {
        return $this->belongsTo(ProjetoImpressaoParteItem::class, 'id_item_projeto');
    }

    public function cor()
    {
        return $this->belongsTo(Cor::class, 'id_cor');
    }

    public function composicaoCor()
    {
        return $this->belongsTo(ProdutoComposicaoCor::class, 'id_composicao_cor');
    }

    public function filamento()
    {
        return $this->hasOne(ProdutoVariacaoFilamento::class, 'id_variacao');
    }
}

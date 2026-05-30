<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoComposicaoCor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produto_composicao_cores';

    protected $fillable = [
        'id_composicao',
        'id_parte',
        'id_item_projeto',
        'tipo_cor',
        'id_cor',
    ];

    protected $casts = [
        'id'              => 'integer',
        'id_composicao'   => 'integer',
        'id_parte'        => 'integer',
        'id_item_projeto' => 'integer',
        'id_cor'          => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
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

    public function variacao()
    {
        return $this->hasOne(ProdutoVariacao::class, 'id_composicao_cor');
    }
}

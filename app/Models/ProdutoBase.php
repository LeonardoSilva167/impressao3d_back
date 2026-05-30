<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produtos_base';

    protected $fillable = [
        'descricao_produto',
        'codigo_base',
        'sku_base',
        'id_categoria',
        'id_modelo',
        'id_linha',
    ];

    protected $casts = [
        'id'            => 'integer',
        'id_categoria'  => 'integer',
        'id_modelo'     => 'integer',
        'id_linha'      => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function categoria()
    {
        return $this->belongsTo(CategoriaProduto::class, 'id_categoria');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloProduto::class, 'id_modelo');
    }

    public function linha()
    {
        return $this->belongsTo(LinhaProduto::class, 'id_linha');
    }

    public function variacoes()
    {
        return $this->hasMany(ProdutoVariacao::class, 'id_produto_base');
    }
}

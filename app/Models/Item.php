<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'itens';

    protected $fillable = [
        'id_categoria_item',
        'descricao',
        'codigo',
        'unidade_medida',
        'controla_estoque',
        'gera_custo',
        'ativo',
    ];

    protected $casts = [
        'id'                => 'integer',
        'id_categoria_item' => 'integer',
        'controla_estoque'  => 'boolean',
        'gera_custo'        => 'boolean',
        'ativo'             => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function categoriaItem()
    {
        return $this->belongsTo(CategoriaItem::class, 'id_categoria_item');
    }

    public function filamento()
    {
        return $this->hasOne(Filamento::class, 'id_item');
    }

    public function comprasItens()
    {
        return $this->hasMany(CompraItem::class, 'id_item');
    }
}

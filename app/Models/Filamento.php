<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'filamentos';

    protected $attributes = [
        'qtd'               => 0,
        'preco_medio_grama' => 0,
    ];

    protected $fillable = [
        'id_tipo_material',
        'id_cor',
        'id_linha_marca',
        'id_marca',
        'id_item',
        'codigo',
        'resumo',
        'qtd',
        'preco_medio_grama',
    ];

    protected $casts = [
        'id'                 => 'integer',
        'id_tipo_material'   => 'integer',
        'id_cor'             => 'integer',
        'id_linha_marca'     => 'integer',
        'id_marca'           => 'integer',
        'id_item'            => 'integer',
        'qtd'                => 'decimal:2',
        'preco_medio_grama'  => 'decimal:4',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function tipoMaterial()
    {
        return $this->belongsTo(TipoMaterial::class, 'id_tipo_material');
    }

    public function cor()
    {
        return $this->belongsTo(Cor::class, 'id_cor');
    }

    public function linhaMarca()
    {
        return $this->belongsTo(LinhaMarca::class, 'id_linha_marca');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}

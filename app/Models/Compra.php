<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'id_plataforma_compra',
        'data_compra',
        'numero_pedido',
        'valor_frete',
        'desconto',
        'valor_total',
        'observacao',
    ];

    protected $casts = [
        'id'                   => 'integer',
        'id_plataforma_compra' => 'integer',
        'data_compra'          => 'date',
        'valor_frete'          => 'decimal:2',
        'desconto'             => 'decimal:2',
        'valor_total'          => 'decimal:2',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'deleted_at'           => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function plataformaCompra()
    {
        return $this->belongsTo(PlataformaCompra::class, 'id_plataforma_compra');
    }

    public function itens()
    {
        return $this->hasMany(CompraItem::class, 'id_compra');
    }
}

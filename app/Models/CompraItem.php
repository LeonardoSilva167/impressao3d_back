<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compras_itens';

    protected $fillable = [
        'id_compra',
        'id_item',
        'qtd',
        'valor_unitario',
        'valor_total',
    ];

    protected $casts = [
        'id'             => 'integer',
        'id_compra'      => 'integer',
        'id_item'        => 'integer',
        'qtd'            => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total'    => 'decimal:2',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'id_compra');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}

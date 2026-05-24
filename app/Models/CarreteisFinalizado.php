<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarreteisFinalizado extends Model
{
    use HasFactory, SoftDeletes;

    public const GRAMATURAS_VALIDAS = [500, 1000];

    protected $table = 'carreteis_finalizados';

    protected $fillable = [
        'id_item',
        'gramatura',
        'quantidade',
        'qtd_total_consumida',
        'observacao',
        'data_finalizacao',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'id_item'             => 'integer',
        'gramatura'           => 'integer',
        'quantidade'          => 'integer',
        'qtd_total_consumida' => 'decimal:4',
        'data_finalizacao'    => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'id_carreteis_finalizados');
    }
}

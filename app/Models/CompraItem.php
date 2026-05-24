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
        'qtd_compra',
        'qtd_interna',
        'qtd_original',
        'qtd_atual',
        'gramatura_filamento',
        'valor_unitario_compra',
        'valor_total',
        'valor_unitario_real',
    ];

    protected $appends = [
        'percentual_utilizado',
    ];

    protected $casts = [
        'id'                    => 'integer',
        'id_compra'             => 'integer',
        'id_item'               => 'integer',
        'qtd_compra'            => 'decimal:4',
        'qtd_interna'           => 'decimal:4',
        'qtd_original'          => 'decimal:4',
        'qtd_atual'             => 'decimal:4',
        'gramatura_filamento'   => 'integer',
        'valor_unitario_compra' => 'decimal:2',
        'valor_total'           => 'decimal:2',
        'valor_unitario_real'   => 'decimal:4',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
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

    public function getPercentualUtilizadoAttribute(): float
    {
        $qtdOriginal = (float) $this->qtd_original;

        if ($qtdOriginal <= 0) {
            return 0;
        }

        $qtdAtual = (float) $this->qtd_atual;

        return round((($qtdOriginal - $qtdAtual) / $qtdOriginal) * 100, 2);
    }

    public static function calcularPercentualUtilizado(float $qtdOriginal, float $qtdAtual): float
    {
        if ($qtdOriginal <= 0) {
            return 0;
        }

        return round((($qtdOriginal - $qtdAtual) / $qtdOriginal) * 100, 2);
    }

    public static function calcularStatus(float $qtdAtual): string
    {
        return $qtdAtual > 0 ? 'ATIVO' : 'ZERADO';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeProdutoItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grade_produto_itens';

    protected $fillable = [
        'id_grade_produto',
        'nome_produto',
        'sku',
        'peso_total',
        'tempo_total',
        'custo_total',
        'status',
    ];

    protected $casts = [
        'id'               => 'integer',
        'id_grade_produto' => 'integer',
        'peso_total'       => 'float',
        'custo_total'      => 'float',
        'status'           => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function gradeProduto()
    {
        return $this->belongsTo(GradeProduto::class, 'id_grade_produto');
    }
}

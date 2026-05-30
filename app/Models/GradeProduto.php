<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeProduto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grades_produtos';

    protected $fillable = [
        'id_produto_base',
        'descricao',
        'status',
    ];

    protected $casts = [
        'id'              => 'integer',
        'id_produto_base' => 'integer',
        'status'          => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function produtoBase()
    {
        return $this->belongsTo(ProdutoBase::class, 'id_produto_base');
    }

    public function partes()
    {
        return $this->hasMany(GradeProdutoParte::class, 'id_grade_produto');
    }

    public function combinacoes()
    {
        return $this->hasMany(GradeProdutoCombinacao::class, 'id_grade_produto');
    }

    public function itens()
    {
        return $this->hasMany(GradeProdutoItem::class, 'id_grade_produto');
    }
}

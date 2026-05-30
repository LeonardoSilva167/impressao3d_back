<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeProdutoCombinacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grade_produto_combinacoes';

    protected $fillable = [
        'id_grade_produto',
        'descricao',
    ];

    protected $casts = [
        'id'               => 'integer',
        'id_grade_produto' => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function gradeProduto()
    {
        return $this->belongsTo(GradeProduto::class, 'id_grade_produto');
    }

    public function partes()
    {
        return $this->hasMany(GradeProdutoCombinacaoParte::class, 'id_grade_produto_combinacao');
    }
}

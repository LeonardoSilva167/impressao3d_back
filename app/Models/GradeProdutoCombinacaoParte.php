<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeProdutoCombinacaoParte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grade_produto_combinacao_partes';

    protected $fillable = [
        'id_grade_produto_combinacao',
        'id_parte_projeto',
        'quantidade',
    ];

    protected $casts = [
        'id'                          => 'integer',
        'id_grade_produto_combinacao' => 'integer',
        'id_parte_projeto'            => 'integer',
        'quantidade'                  => 'integer',
        'created_at'                  => 'datetime',
        'updated_at'                  => 'datetime',
        'deleted_at'                  => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function combinacao()
    {
        return $this->belongsTo(GradeProdutoCombinacao::class, 'id_grade_produto_combinacao');
    }

    public function parteProjeto()
    {
        return $this->belongsTo(ProjetoImpressaoParte::class, 'id_parte_projeto');
    }
}

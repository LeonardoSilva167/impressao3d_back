<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeProdutoParte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grade_produto_partes';

    protected $fillable = [
        'id_grade_produto',
        'id_parte_projeto',
    ];

    protected $casts = [
        'id'               => 'integer',
        'id_grade_produto' => 'integer',
        'id_parte_projeto' => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function gradeProduto()
    {
        return $this->belongsTo(GradeProduto::class, 'id_grade_produto');
    }

    public function parteProjeto()
    {
        return $this->belongsTo(ProjetoImpressaoParte::class, 'id_parte_projeto');
    }
}

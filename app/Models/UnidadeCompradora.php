<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadeCompradora extends Model
{
    use HasFactory,  SoftDeletes;
    
    protected $table = 'unidade_compradoras';

    protected $fillable = [
        'orgaos_id', 
        'codigo', 
        'nome', 
        'uf', 
        'cidade', 
    ];

    protected $casts = [
        'orgaos_id' => 'integer',
        'codigo' => 'string',
        'nome' => 'string',
        'uf' => 'string',
        'cidade' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
    
    public function orgaos()
    {
        return $this->belongsTo(Orgao::class, 'orgaos_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinhaMarca extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'linhas_marcas';

    protected $fillable = [
        'descricao',
    ];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
}

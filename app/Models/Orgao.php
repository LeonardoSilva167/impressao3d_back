<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orgao extends Model
{
    use HasFactory,  SoftDeletes;
    
    protected $table = 'orgaos';

    protected $fillable = [
        'orgao_nome', 
    ];

    protected $casts = [
        'orgao_nome' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusCotacao extends Model
{
    use HasFactory,  SoftDeletes;
    
    protected $table = 'status_cotacoes';

    protected $fillable = [
        'nome', 
    ];

    protected $casts = [
        'nome' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
}


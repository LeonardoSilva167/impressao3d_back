<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlataformaCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plataforma_compras';

    protected $fillable = [
        'descricao',
        'url',
    ];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'id_plataforma_compra');
    }
}
